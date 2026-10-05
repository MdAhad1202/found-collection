<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'event_collection';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

// Auto cleanup: Delete activity logs older than 90 days (1% chance per request)
if (rand(1, 100) === 1) {
    $conn->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
}

// Session timeout (30 minutes)
if (isset($_SESSION['admin_id'])) {
    $timeout = 30 * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header("Location: /event-collection/login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}
?>