<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$search = trim($_GET['search'] ?? '');
$where = "WHERE s.id NOT IN (SELECT DISTINCT student_id FROM collections)";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (s.roll LIKE ? OR s.name LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s];
    $types = 'ss';
}

$sql = "SELECT s.* FROM students s $where ORDER BY s.roll ASC";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$totalUnpaid = $res->num_rows;
$totalStudents = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
?>
<div class="d-flex gap-2">
    <a href="download-unpaid.php" class="btn btn-outline-success">
        📥 Unpaid Only
    </a>
    <a href="download-combined.php" class="btn btn-success">
        📥 Paid + Unpaid (Combined)
    </a>
</div>

<div class="alert alert-warning">
    <strong>মোট:</strong> <?= $totalStudents ?> জন student-এর মধ্যে 
    <strong><?= $totalUnpaid ?> জন</strong> এখনো কোনো payment করেনি।
</div>

<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" 
               placeholder="🔍 Roll / Name দিয়ে খুঁজুন..." 
               value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="unpaid-list.php" class="btn btn-secondary">Clear</a>
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
                   
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res->num_rows === 0): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">
                    🎉 সব student payment করেছে!
                </td></tr>
            <?php else: $i=1; while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($r['roll']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    
                    <td><span class="badge bg-danger">❌ Unpaid</span></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>