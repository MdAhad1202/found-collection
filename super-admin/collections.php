<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

// Filter
$adminFilter = (int)($_GET['admin'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$where = "WHERE DATE(c.created_at) BETWEEN '$from' AND '$to'";
if ($adminFilter > 0) $where .= " AND c.admin_id = $adminFilter";

// Stats
$stats = $conn->query("
    SELECT COUNT(*) AS entries,
           COUNT(DISTINCT student_id) AS students,
           COALESCE(SUM(amount), 0) AS total
    FROM collections c
    $where
")->fetch_assoc();

// Collections
$res = $conn->query("
    SELECT c.*, s.roll, s.name, a.name AS collector 
    FROM collections c 
    JOIN students s ON c.student_id = s.id 
    JOIN admins a ON c.admin_id = a.id 
    $where
    ORDER BY c.created_at DESC
    LIMIT 500
");

// Admins for filter
$admins = $conn->query("SELECT id, name FROM admins WHERE role='admin' ORDER BY name");
?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        🗑️ Collection entry সফলভাবে delete হয়েছে।
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">💰 All Collections</h2>
    <a href="download-all-collections.php?from=<?= $from ?>&to=<?= $to ?>&admin=<?= $adminFilter ?>" 
       class="btn btn-success">
        📥 Download Excel
    </a>
</div>

<!-- Filter -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Admin</label>
                <select name="admin" class="form-select">
                    <option value="0">সব Admin</option>
                    <?php while ($a = $admins->fetch_assoc()): ?>
                        <option value="<?= $a['id'] ?>" <?= $adminFilter==$a['id']?'selected':'' ?>>
                            <?= htmlspecialchars($a['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" value="<?= $from ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" value="<?= $to ?>" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary me-2">🔍 Filter</button>
                <a href="collections.php" class="btn btn-secondary">Reset</a>
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
                    <th>Date</th>
                    <th>Roll</th>
                    <th>Name</th>
                    <th>Collector</th>
                    <th>Note</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">এই filter-এ কোনো entry নেই</td></tr>
            <?php else: $i=1; while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d-M-Y h:i A', strtotime($r['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['roll']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($r['collector']) ?></span></td>
                    <td><small><?= htmlspecialchars($r['note'] ?? '—') ?></small></td>
                    <td class="text-end text-success fw-bold">৳ <?= number_format($r['amount'], 2) ?></td>
                    <td class="text-end">
                        <a href="edit-collection.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>