<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$aid = $_SESSION['admin_id'];

// Today's stats
$today = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM collections WHERE admin_id=$aid AND DATE(created_at)=CURDATE()")->fetch_assoc()['t'];
$today_count = $conn->query("SELECT COUNT(*) AS c FROM collections WHERE admin_id=$aid AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];

// Total stats
$total = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM collections WHERE admin_id=$aid")->fetch_assoc()['t'];
$total_count = $conn->query("SELECT COUNT(*) AS c FROM collections WHERE admin_id=$aid")->fetch_assoc()['c'];
$students = $conn->query("SELECT COUNT(DISTINCT student_id) AS c FROM collections WHERE admin_id=$aid")->fetch_assoc()['c'];

// Total students in system
$all_students = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];

// Recent collections
$recent = $conn->query("
    SELECT c.*, s.roll, s.name 
    FROM collections c 
    JOIN students s ON c.student_id = s.id 
    WHERE c.admin_id = $aid 
    ORDER BY c.created_at DESC 
    LIMIT 10
");
?>

<h2 class="mb-4">👤 Admin Dashboard</h2>

<div class="alert alert-info">
    <strong>স্বাগতম, <?= htmlspecialchars($_SESSION['name']) ?>!</strong>
    <br>
    <small>আপনি শুধু নিজের collection দেখতে এবং করতে পারবেন।</small>
</div>

<div class="row g-3">
    <!-- Today -->
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">আজকের Collection</h6>
                        <h3 class="mb-0">৳ <?= number_format($today, 2) ?></h3>
                        <small><?= $today_count ?> entries</small>
                    </div>
                    <div class="icon">📅</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total -->
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">আমার Total</h6>
                        <h3 class="mb-0">৳ <?= number_format($total, 2) ?></h3>
                        <small><?= $total_count ?> entries</small>
                    </div>
                    <div class="icon">💰</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students -->
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">Collected Students</h6>
                        <h3 class="mb-0"><?= $students ?> / <?= $all_students ?></h3>
                        <small>unique students</small>
                    </div>
                    <div class="icon">👥</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Action -->
    <div class="col-md-3">
        <div class="card stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white text-center">
                <h6>নতুন Collection</h6>
                <a href="collect.php" class="btn btn-light mt-2">
                    <i class="bi bi-cash-coin"></i> Collect Money
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Collections -->
<div class="card stat-card mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 সাম্প্রতিক Collections</h5>
        <a href="my-collections.php" class="btn btn-sm btn-outline-primary">সব দেখুন →</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date & Time</th>
                    <th>Roll</th>
                    <th>Name</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recent->num_rows === 0): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        এখনো কোনো collection করা হয়নি<br>
                        <a href="collect.php" class="btn btn-sm btn-primary mt-2">💰 প্রথম Collection করুন</a>
                    </td>
                </tr>
            <?php else: while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d-M-Y h:i A', strtotime($r['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['roll']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td class="text-end text-success fw-bold">৳ <?= number_format($r['amount'], 2) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
