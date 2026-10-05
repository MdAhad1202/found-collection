<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$aid = $_SESSION['admin_id'];

// Filter
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$where = "WHERE c.admin_id = $aid AND DATE(c.created_at) BETWEEN '$from' AND '$to'";

// Stats
$stats = $conn->query("
    SELECT 
        COUNT(*) AS entries,
        COUNT(DISTINCT student_id) AS students,
        COALESCE(SUM(amount), 0) AS total
    FROM collections c
    $where
")->fetch_assoc();

$res = $conn->query("
    SELECT c.*, s.roll, s.name 
    FROM collections c 
    JOIN students s ON c.student_id = s.id 
    $where
    ORDER BY c.created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📋 My Collections</h2>
    <a href="download-chart.php" class="btn btn-success">
        📥 Download Excel
    </a>
</div>

<!-- Filter -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <label class="form-label">From Date</label>
                <input type="date" name="from" value="<?= $from ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date</label>
                <input type="date" name="to" value="<?= $to ?>" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary me-2">🔍 Filter</button>
                <a href="my-collections.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6 class="mb-1">Total Entries</h6>
                <h3 class="mb-0"><?= $stats['entries'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <h6 class="mb-1">Unique Students</h6>
                <h3 class="mb-0"><?= $stats['students'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <h6 class="mb-1">Total Amount</h6>
                <h3 class="mb-0">৳ <?= number_format($stats['total'], 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card stat-card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date & Time</th>
                    <th>Roll</th>
                    <th>Name</th>
                    <th>Note</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res->num_rows === 0): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        এই date range-এ কোনো collection নেই
                    </td>
                </tr>
            <?php else: $i=1; while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-M-Y h:i A', strtotime($r['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['roll']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($r['note'] ?? '—') ?></small></td>
                    <td class="text-end text-success fw-bold">৳ <?= number_format($r['amount'], 2) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>