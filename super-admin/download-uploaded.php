<?php
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM uploads WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u) die("File পাওয়া যায়নি");

$path = __DIR__ . '/../uploads/students/' . $u['file_name'];
if (!file_exists($path)) die("File নেই");

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $u['original_name'] . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
?>