<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Collection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/event-collection/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar-app">
    <a href="<?= $_SESSION['role']==='super' ? '/event-collection/super-admin/dashboard.php' : '/event-collection/admin/dashboard.php' ?>" class="brand">
        <span class="brand-icon">📊</span>
        Event Collection
    </a>
    <div class="user-info">
        <a href="/event-collection/change-password.php" class="text-white text-decoration-none d-flex align-items-center gap-2" title="Change Password">
            <i class="bi bi-person-circle" style="font-size: 20px;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
            <span class="role-badge <?= $_SESSION['role'] ?>">
                <?= $_SESSION['role'] ?>
            </span>
            <i class="bi bi-gear-fill" style="font-size: 11px; opacity: 0.7;"></i>
        </a>
        <a href="/event-collection/logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar-app">
    <ul class="nav flex-column">
        <?php if ($_SESSION['role'] === 'super'): ?>
            <li class="sidebar-heading">Main</li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a></li>
            
            <li class="sidebar-heading">Management</li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/admins.php">
                <i class="bi bi-people-fill"></i> Admins
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/students.php">
                <i class="bi bi-mortarboard-fill"></i> Students
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/upload-excel.php">
                <i class="bi bi-cloud-upload-fill"></i> Upload Excel
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/uploads-list.php">
                <i class="bi bi-folder-fill"></i> Upload History
            </a></li>
            
            <li class="sidebar-heading">Reports</li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/collections.php">
                <i class="bi bi-cash-stack"></i> All Collections
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/reports.php">
                <i class="bi bi-graph-up"></i> Reports
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/unpaid-list.php">
                <i class="bi bi-exclamation-triangle-fill"></i> Unpaid List
            </a></li>
            
            <li class="sidebar-heading">System</li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/activity-logs.php">
                <i class="bi bi-journal-text"></i> Activity Log
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/super-admin/backups.php">
    <i class="bi bi-hdd-stack"></i> Backups
</a></li>
        <?php else: ?>
            <li class="sidebar-heading">Main</li>
            <li><a class="sidebar-link" href="/event-collection/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a></li>
            
            <li class="sidebar-heading">Collection</li>
            <li><a class="sidebar-link" href="/event-collection/admin/collect.php">
                <i class="bi bi-cash-coin"></i> Collect Money
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/admin/my-collections.php">
                <i class="bi bi-list-ul"></i> My Collections
            </a></li>
            
            <li class="sidebar-heading">Reports</li>
            <li><a class="sidebar-link" href="/event-collection/admin/student-status.php">
                <i class="bi bi-people"></i> Student Status
            </a></li>
            <li><a class="sidebar-link" href="/event-collection/admin/download-chart.php">
                <i class="bi bi-download"></i> Download Chart
            </a></li>
        <?php endif; ?>
    </ul>
</aside>

<!-- Main Content -->
<main class="main-app fade-in">