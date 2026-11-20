<?php
require __DIR__ . '/_inc/bootstrap.php';
require_login();
require_post_csrf();
$pdo = admin_db();

$schema = settings_schema();
$current = settings();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group = (string)($_POST['group'] ?? '');
    if (!isset($schema[$group])) {
        flash('error', 'Unknown settings section.');
        redirect('admin/settings.php');
    }

    $save = [];
    $pendingImages = [];
    foreach ($schema[$group] as $key => $def) {
        $type = $def['type'];
        if ($type === 'image') { $pendingImages[$key] = $def; continue; }
        if ($type === 'bool') { $save[$key] = !empty($_POST[$key]) ? '1' : '0'; continue; }
        if ($type === 'code') {
            $value = str_replace("\r\n", "\n", (string)($_POST[$key] ?? ''));
            $save[$key] = mb_substr(trim($value), 0, 5000);
            continue;
        }
        $value = input($key, $type === 'text' || $type === 'email' || $type === 'url' ? 300 : 3000);
        if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$key] = $def['label'] . ': please enter a valid email address.';
        }
        if ($type === 'url' && !is_valid_http_url($value)) {
            $errors[$key] = $def['label'] . ': please enter a full URL starting with https://';
        }
        if ($key === 'map_embed_url' && $value !== '' && !preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $value)) {
            $errors[$key] = 'Map URL must be a Google Maps "Embed a map" link (https://www.google.com/maps/embed?...).';
        }
        if ($key === 'site_name' && $value === '') { $errors[$key] = 'Company name cannot be empty.'; }
        $save[$key] = $value;
    }

    if (!$errors) {
        foreach ($pendingImages as $key => $def) {
            $existing = $current[$key] ?? '';
            [$path, $imgError] = process_image_field($key, $existing !== '' ? $existing : null);
            if ($imgError) { $errors[$key] = $def['label'] . ': ' . $imgError; continue; }
            // Removing an image falls back to the bundled default.
            $save[$key] = $path ?? (string)$def['default'];
        }
    }

    if (!$errors && $save) {
        // One multi-row upsert for the whole section.
        $placeholders = implode(',', array_fill(0, count($save), '(?, ?)'));
        $params = [];
        foreach ($save as $k => $v) { $params[] = $k; $params[] = $v; }
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES $placeholders
                       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute($params);
        flash('success', $group . ' saved.');
        redirect('admin/settings.php?tab=' . rawurlencode($group));
    }
    // Re-display submitted values on error.
    $current = array_merge($current, $save);
    $_GET['tab'] = $group;
}

$tab = isset($schema[$_GET['tab'] ?? '']) ? $_GET['tab'] : array_key_first($schema);

admin_header('Site settings', 'settings');
?>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>

<nav class="tabs" aria-label="Settings sections">
<?php foreach (array_keys($schema) as $g): ?>
  <a href="?tab=<?= e(rawurlencode($g)) ?>" class="<?= $g === $tab ? 'active' : '' ?>"><?= e($g) ?></a>
<?php endforeach; ?>
</nav>

<form method="post" enctype="multipart/form-data" class="card settings-form">
  <?= csrf_field() ?>
  <input type="hidden" name="group" value="<?= e($tab) ?>">
<?php if (in_array($tab, ['Homepage', 'About page', 'Capabilities page'], true)): ?>
  <p class="help-box">Formatting tip for headings: wrap words in <code>*asterisks*</code> for the italic accent style, and press Enter for a line break.</p>
<?php endif; ?>
<?php foreach ($schema[$tab] as $key => $def):
    $value = (string)($current[$key] ?? $def['default']);
    $help = $def['help'] ?? '';
    $isErr = isset($errors[$key]) ? ' has-error' : ''; ?>
<?php if ($def['type'] === 'image'): ?>
  <?php image_field($key, $value !== '' ? $value : null, $def['label']); ?>
<?php elseif ($def['type'] === 'bool'): ?>
  <label class="inline field"><input type="checkbox" name="<?= e($key) ?>" value="1"<?= checked($value === '1') ?>> <?= e($def['label']) ?></label>
<?php elseif (in_array($def['type'], ['textarea', 'rich', 'code'], true)): ?>
  <label class="field<?= $isErr ?>"><?= e($def['label']) ?>
    <textarea name="<?= e($key) ?>" rows="<?= (int)($def['rows'] ?? ($def['type'] === 'code' ? 6 : 3)) ?>"<?= $def['type'] === 'code' ? ' class="mono" spellcheck="false"' : '' ?>><?= e($value) ?></textarea>
<?php if ($help !== ''): ?><small class="help"><?= e($help) ?></small><?php endif; ?>
  </label>
<?php else: ?>
  <label class="field<?= $isErr ?>"><?= e($def['label']) ?>
    <input type="<?= $def['type'] === 'email' ? 'email' : ($def['type'] === 'url' ? 'url' : 'text') ?>" name="<?= e($key) ?>" value="<?= e($value) ?>" maxlength="300">
<?php if ($help !== ''): ?><small class="help"><?= e($help) ?></small><?php endif; ?>
  </label>
<?php endif; ?>
<?php endforeach; ?>
  <div class="form-actions"><button class="btn" type="submit">Save <?= e(strtolower($tab)) ?></button></div>
</form>
<?php admin_footer(); ?>
