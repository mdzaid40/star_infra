<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=UTF-8');
$bp = base_path();
echo "User-agent: *\n";
echo "Disallow: {$bp}/admin/\n";
echo "\nSitemap: " . abs_url('sitemap.php') . "\n";
