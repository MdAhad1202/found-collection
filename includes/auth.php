<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('/event-collection/login.php');
}

function requireSuperAdmin() {
    if ($_SESSION['role'] !== 'super') {
        redirect('/event-collection/admin/dashboard.php');
    }
}

function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        redirect('/event-collection/super-admin/dashboard.php');
    }
}
?>