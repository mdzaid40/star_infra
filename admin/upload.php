<?php
/** AJAX image upload used by the blog editor. Returns JSON. */
require __DIR__ . '/_inc/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

$respond = static function (int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $respond(405, ['error' => 'Method not allowed']); }
if (!current_user()) { $respond(401, ['error' => 'Your session has expired. Please log in again in another tab.']); }
if (!csrf_verify()) { $respond(419, ['error' => 'Security token expired. Reload the page.']); }

// post_max_size exceeded → PHP silently empties $_FILES/$_POST
if (empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $respond(413, ['error' => 'The image is too large (max ' . upload_limit_label() . ').']);
}

$error = null;
$path = handle_image_upload($_FILES['image'] ?? null, $error);
if (!$path) { $respond(422, ['error' => $error ?: 'Please choose an image.']); }

$respond(200, ['url' => image_url($path), 'path' => $path]);
