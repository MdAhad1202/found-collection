<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$sid = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "<div class='alert alert-danger'>❌ Student পাওয়া যায়নি। 
          <a href='students.php'>Students-এ ফিরুন</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// All payments for this student
$stmt = $conn->prepare("
    SELECT c.*, a.name AS collector
    FROM collections c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.student_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $sid);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grandTotal = 0;
$perAdmin = [];
foreach ($payments as $p) {
    $grandTotal += $p['amount'];
    if (!isset($perAdmin[$p['collector']])) {
        $perAdmin[$p['collector']] = ['count' => 0, 'total' => 0];
    }
    $perAdmin[$p['collector']]['count']++;
    $perAdmin[$p['collector']]['total'] += $p['amount'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">🎓 Student Detail</h2>
    <a href="students.php" class="btn btn-secondary">← Back</a>
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
    <div class="col-md-6">
        <div class="card stat-card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="mb-1">💰 সর্বমোট Collected</h5>
                <h1 class="mb-0">৳ <?= number_format($grandTotal, 2) ?></h1>
                <small><?= count($payments) ?> বারের payment</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6>👥 Admin-wise Breakdown</h6>
                <?php if (empty($perAdmin)): ?>
                    <p class="mb-0">এখনো কোনো payment নেই</p>
                <?php else: foreach ($perAdmin as $name => $d): ?>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span><?= htmlspecialchars($name) ?> (<?= $d['count'] ?> বার)</span>
                        <strong>৳ <?= number_format($d['total'], 2) ?></strong>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- All Payments -->
<div class="card stat-card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">📋 সব Payment History</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date & Time</th>
                    <th>Amount</th>
                    <th>Collector</th>
                    <th>Note</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">কোনো payment নেই</td></tr>
            <?php else: $i=1; foreach ($payments as $p): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-M-Y h:i A', strtotime($p['created_at'])) ?></td>
                    <td class="fw-bold text-success">৳ <?= number_format($p['amount'], 2) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($p['collector']) ?></span></td>
                    <td><small><?= htmlspecialchars($p['note'] ?? '—') ?></small></td>
                    <td class="text-end">
                        <a href="edit-collection.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($payments)): ?>
            <tfoot>
                <tr class="table-success">
                    <th colspan="2" class="text-end">সর্বমোট:</th>
                    <th>৳ <?= number_format($grandTotal, 2) ?></th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>