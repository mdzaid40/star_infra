<?php
/**
 * Secure image uploads: type checked by file content (not extension),
 * random file names, stored under /uploads/YYYY/MM/, large images resized.
 */
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

/**
 * @param array|null $file  An entry from $_FILES
 * @return string|null       Relative path (e.g. "uploads/2026/11/name.jpg"), or null if nothing uploaded / on error
 */
function handle_image_upload(?array $file, ?string &$error = null): ?string
{
    $error = null;
    if (!$file || !isset($file['error']) || is_array($file['error'])) { return null; }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) { return null; }

    $maxBytes = (int)UPLOAD_MAX_MB * 1024 * 1024;
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: $error = 'The image is too large (max ' . upload_limit_label() . ').'; return null;
        case UPLOAD_ERR_PARTIAL:   $error = 'The upload was interrupted. Please try again.'; return null;
        default:                   $error = 'The server could not save the upload (code ' . (int)$file['error'] . ').'; return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) { $error = 'Invalid upload.'; return null; }
    if ($file['size'] > $maxBytes) { $error = 'The image is too large (max ' . upload_limit_label() . ').'; return null; }

    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = '';
    if (class_exists('finfo')) {
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    }
    $info = @getimagesize($file['tmp_name']);
    if ($mime === '' && $info) { $mime = $info['mime']; }
    if (!isset($types[$mime]) || !$info) {
        $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        return null;
    }

    $relDir = 'uploads/' . date('Y/m');
    $absDir = APP_ROOT . '/' . $relDir;
    if (!is_dir($absDir) && !@mkdir($absDir, 0755, true)) {
        $error = 'Upload folder is not writable. Set permissions of /uploads to 755.';
        return null;
    }

    $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME));
    $base = substr($base !== '' ? $base : 'image', 0, 50);
    $name = $base . '-' . bin2hex(random_bytes(4)) . '.' . $types[$mime];
    $dest = $absDir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error = 'Could not save the uploaded image.';
        return null;
    }
    @chmod($dest, 0644);
    resize_image_if_needed($dest, $mime);

    return $relDir . '/' . $name;
}

function upload_limit_label(): string
{
    $toBytes = static function ($v): int {
        $v = trim((string)$v);
        if ($v === '' || $v === '-1') { return PHP_INT_MAX; }
        $n = (int)$v;
        switch (strtolower(substr($v, -1))) { case 'g': $n *= 1024; // no break
            case 'm': $n *= 1024; // no break
            case 'k': $n *= 1024; }
        return $n;
    };
    $limit = min((int)UPLOAD_MAX_MB * 1024 * 1024, $toBytes(ini_get('upload_max_filesize')), $toBytes(ini_get('post_max_size')));
    return round($limit / 1048576, 1) . ' MB';
}

/** Downscale very large photos (e.g. straight from a phone camera) to IMAGE_MAX_WIDTH. */
function resize_image_if_needed(string $path, string $mime): void
{
    if (!function_exists('imagecreatetruecolor') || $mime === 'image/gif') { return; }
    $info = @getimagesize($path);
    if (!$info) { return; }
    [$w, $h] = $info;
    $max = (int)IMAGE_MAX_WIDTH;
    $orientation = 1;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientation = (int)($exif['Orientation'] ?? 1);
    }
    if ($w <= $max && $h <= $max && $orientation === 1) { return; }

    // Skip if decoding would likely exceed the memory limit.
    $limit = ini_get('memory_limit');
    if ($limit !== false && $limit !== '-1') {
        $bytes = (int)$limit * (stripos($limit, 'g') !== false ? 1073741824 : (stripos($limit, 'm') !== false ? 1048576 : 1));
        if ($w * $h * 5 * 1.8 > $bytes - memory_get_usage()) { return; }
    }

    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png':  $src = @imagecreatefrompng($path); break;
        case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
        default: $src = false;
    }
    if (!$src) { return; }

    // Apply camera rotation so the photo is upright after re-encoding.
    if ($orientation === 3) { $src = imagerotate($src, 180, 0); }
    elseif ($orientation === 6) { $src = imagerotate($src, -90, 0); }
    elseif ($orientation === 8) { $src = imagerotate($src, 90, 0); }
    $w = imagesx($src); $h = imagesy($src);

    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($mime !== 'image/jpeg') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    switch ($mime) {
        case 'image/jpeg': imagejpeg($dst, $path, 82); break;
        case 'image/png':  imagepng($dst, $path, 7); break;
        case 'image/webp': imagewebp($dst, $path, 82); break;
    }
    imagedestroy($src);
    imagedestroy($dst);
}

/** Delete an uploaded file (only ever inside /uploads). */
function delete_upload(?string $relPath): void
{
    $relPath = (string)$relPath;
    if (strpos($relPath, 'uploads/') !== 0 || strpos($relPath, '..') !== false) { return; }
    $abs = realpath(APP_ROOT . '/' . $relPath);
    $uploads = realpath(APP_ROOT . '/uploads');
    if ($abs && $uploads && strpos($abs, $uploads . DIRECTORY_SEPARATOR) === 0 && is_file($abs)) {
        @unlink($abs);
    }
}
