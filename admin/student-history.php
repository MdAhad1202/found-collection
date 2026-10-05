<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$aid = $_SESSION['admin_id'];

$sid = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "<div class='alert alert-danger'>❌ Student পাওয়া যায়নি। <a href='collect.php?tab=overview'>Overview-তে ফিরুন</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// All payments (everyone's)
$stmt = $conn->prepare("
    SELECT c.*, a.name AS collector, a.id AS collector_id
    FROM collections c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.student_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $sid);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grandTotal = 0;
$myTotal = 0;
$myTimes = 0;
$perAdmin = [];

foreach ($payments as $p) {
    $grandTotal += $p['amount'];
    if ($p['collector_id'] == $aid) {
        $myTotal += $p['amount'];
        $myTimes++;
    }
    if (!isset($perAdmin[$p['collector']])) {
        $perAdmin[$p['collector']] = ['count' => 0, 'total' => 0];
    }
    $perAdmin[$p['collector']]['count']++;
    $perAdmin[$p['collector']]['total'] += $p['amount'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">🎓 Student History</h2>
    <a href="collect.php?tab=overview" class="btn btn-secondary">← Overview</a>
</div>

<!-- Student Info -->
<div class="card stat-card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Student Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><strong>Roll:</strong><br><span class="fs-5"><?= htmlspecialchars($student['roll']) ?></span></div>
            <div class="col-md-6"><strong>Name:</strong><br><span class="fs-5"><?= htmlspecialchars($student['name']) ?></span></div>
        </div>
    </div>
</div>

<!-- Total Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="mb-1">সর্বমোট Collected</h6>
                <h2 class="mb-0">৳ <?= number_format($grandTotal, 2) ?></h2>
                <small><?= count($payments) ?> entries</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body text-center">
                <h6 class="mb-1">আপনার Collection</h6>
                <h2 class="mb-0">৳ <?= number_format($myTotal, 2) ?></h2>
                <small><?= $myTimes ?> বার</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-warning text-dark">
            <div class="card-body text-center">
                <h6 class="mb-1">অন্যান্য Admin</h6>
                <h2 class="mb-0">৳ <?= number_format($grandTotal - $myTotal, 2) ?></h2>
                <small><?= count($payments) - $myTimes ?> entries</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Collect Button -->
<div class="mb-3">
    <a href="collect.php?tab=search&search=<?= urlencode($student['roll']) ?>" 
       class="btn btn-success btn-lg">
        💰 এই Student থেকে Collect করুন
    </a>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card stat-card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📋 সব Payment</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Collector</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">কোনো payment নেই</td></tr>
                    <?php else: $i=1; foreach ($payments as $p): 
                        $mine = $p['collector_id'] == $aid;
                    ?>
                        <tr class="<?= $mine ? 'table-success' : '' ?>">
                            <td><?= $i++ ?></td>
                            <td><?= date('d-M-Y h:i A', strtotime($p['created_at'])) ?></td>
                            <td class="fw-bold">৳ <?= number_format($p['amount'], 2) ?></td>
                            <td>
                                <?= htmlspecialchars($p['collector']) ?>
                                <?php if ($mine): ?>
                                    <span class="badge bg-success">আপনি</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($p['note'] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card stat-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">👥 Admin-wise Breakdown</h5>
            </div>
            <div class="card-body">
                <?php if (empty($perAdmin)): ?>
                    <p class="text-muted mb-0">এখনো কোনো payment নেই</p>
                <?php else: foreach ($perAdmin as $name => $d): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>
                            <strong><?= htmlspecialchars($name) ?></strong>
                            <br><small class="text-muted"><?= $d['count'] ?> বার</small>
                        </span>
                        <strong class="text-success">৳ <?= number_format($d['total'], 2) ?></strong>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>