<?php
require __DIR__ . '/_inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    admin_logout();
    flash('success', 'You have been logged out.');
}
redirect('admin/login.php');
