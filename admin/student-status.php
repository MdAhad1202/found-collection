<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$aid = $_SESSION['admin_id'];

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
$types = '';

if ($search) {
    $where = "WHERE s.roll LIKE ? OR s.name LIKE ?";
    $s = "%$search%";
    $params = [$s, $s];
    $types = 'ss';
}

$sql = "
    SELECT s.id, s.roll, s.name,
           COALESCE(SUM(CASE WHEN c.admin_id = $aid THEN c.amount END), 0) AS my_amount,
           COUNT(CASE WHEN c.admin_id = $aid THEN 1 END) AS my_times,
           COUNT(c.id) AS total_entries
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

$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$myStudents = $conn->query("SELECT COUNT(DISTINCT student_id) AS c FROM collections WHERE admin_id=$aid")->fetch_assoc()['c'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">🎓 Student Status</h2>
    <div>
        <span class="badge bg-primary">Total: <?= $totalStudents ?></span>
        <span class="badge bg-success">আপনার Collected: <?= $myStudents ?></span>
    </div>
</div>

<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" 
               placeholder="🔍 Roll / Name দিয়ে খুঁজুন..." 
               value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="student-status.php" class="btn btn-secondary">Clear</a>
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
                    <th class="text-end">My Collection</th>
                    <th>My Times</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">কোনো student নেই</td></tr>
            <?php else: $i=1; while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($r['roll']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td class="text-end">
                        <?php if ($r['my_amount'] > 0): ?>
                            <span class="text-success fw-bold">৳ <?= number_format($r['my_amount'], 2) ?></span>
                        <?php else: ?>
                            <span class="text-muted">৳ 0.00</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['my_times'] > 0): ?>
                            <span class="badge bg-info"><?= $r['my_times'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['my_times'] > 0): ?>
                            <span class="badge bg-success">✅ আপনি নিয়েছেন</span>
                        <?php elseif ($r['total_entries'] > 0): ?>
                            <span class="badge bg-warning">⚠️ অন্য Admin নিয়েছে</span>
                        <?php else: ?>
                            <span class="badge bg-danger">❌ Unpaid</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>