<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
$types = '';

if ($search) {
    $where = "WHERE s.roll LIKE ? OR s.name LIKE ? OR s.phone LIKE ?";
    $s = "%$search%";
    $params = [$s, $s, $s];
    $types = 'sss';
}

// Stats
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$totalPaid = $conn->query("SELECT COUNT(DISTINCT student_id) AS c FROM collections")->fetch_assoc()['c'];
$totalUnpaid = $totalStudents - $totalPaid;

$sql = "
    SELECT s.*, 
           COALESCE(SUM(c.amount), 0) AS total_paid,
           COUNT(c.id) AS times
    FROM students s
    LEFT JOIN collections c ON c.student_id = s.id
    $where
    GROUP BY s.id
    ORDER BY s.roll ASC
";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">🎓 Student Management</h2>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary">Total: <?= $totalStudents ?></span>
        <span class="badge bg-success">Paid: <?= $totalPaid ?></span>
        <span class="badge bg-danger">Unpaid: <?= $totalUnpaid ?></span>
        <a href="download-combined.php" class="btn btn-success btn-sm">
            📥 Download Paid+Unpaid
        </a>
    </div>
</div>

<!-- Search -->
<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" 
               placeholder="🔍 Roll / Name দিয়ে খুঁজুন..." 
               value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="students.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card stat-card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Roll</th>
                    <th>Name</th>
                    <th>Total Paid</th>
                    <th>Times</th>
                    <th>Status</th>
                   <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res->num_rows === 0): ?>
                <tr>
                   <td colspan="7" class="text-center text-muted py-4">
                        <?= $search ? 'কোনো student পাওয়া যায়নি' : 'এখনো কোনো student নেই' ?>
                    </td>
                </tr>
            <?php else: $i=1; while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
    <a href="student-detail.php?id=<?= $r['id'] ?>" class="text-decoration-none">
        <strong><?= htmlspecialchars($r['roll']) ?></strong>
    </a>
</td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                   
                    <td>
                        <?php if ($r['total_paid'] > 0): ?>
                            <span class="text-success fw-bold">৳ <?= number_format($r['total_paid'], 2) ?></span>
                        <?php else: ?>
                            <span class="text-muted">৳ 0.00</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['times'] > 0): ?>
                            <span class="badge bg-info"><?= $r['times'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
    <a href="student-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-info">
        👁️ View
    </a>
</td>
                    <td>
                        <?php if ($r['total_paid'] > 0): ?>
                            <span class="badge bg-success">✅ Paid</span>
                        <?php else: ?>
                            <span class="badge bg-danger">⚠️ Unpaid</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>