<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    logActivity($conn, $_SESSION['admin_id'], 'logout', 'Logged out');
}

session_destroy();
header("Location: /event-collection/login.php");
exit;
?>