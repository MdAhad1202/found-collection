<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$aid = $_SESSION['admin_id'];

$tab = $_GET['tab'] ?? 'search';

$student = null;
$history = [];
$msg = '';
$msg_type = 'info';

// Save Collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $sid = (int)$_POST['student_id'];
    $amount = (float)$_POST['amount'];
    $note = trim($_POST['note'] ?? '');
    
    if ($amount <= 0) {
        $msg = "❌ Amount ০-এর বেশি হতে হবে";
        $msg_type = 'danger';
    } else {
        $check = $conn->prepare("SELECT id, roll, name FROM students WHERE id = ?");
        $check->bind_param("i", $sid);
        $check->execute();
        $stu = $check->get_result()->fetch_assoc();
        
        if (!$stu) {
            $msg = "❌ Student পাওয়া যায়নি";
            $msg_type = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO collections (student_id, admin_id, amount, note) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $sid, $aid, $amount, $note);
            
            if ($stmt->execute()) {
                logActivity($conn, $aid, 'collection_add', "Student: {$stu['roll']}, Amount: $amount");
                $msg = "✅ <strong>{$stu['name']}</strong> (Roll: {$stu['roll']}) থেকে ৳ " . number_format($amount, 2) . " collect করা হয়েছে!";
                $msg_type = 'success';
                $_GET['search'] = $stu['roll'];
                $tab = 'search';
            }
        }
    }
}

// Search Student
if ($tab === 'search' && isset($_GET['search']) && $_GET['search'] !== '') {
    $search = trim($_GET['search']);
    $q = "%$search%";
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE roll = ? OR name LIKE ? LIMIT 1");
    $stmt->bind_param("ss", $search, $q);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        $stmt = $conn->prepare("
            SELECT c.*, a.name AS collector, a.id AS collector_id
            FROM collections c 
            JOIN admins a ON c.admin_id = a.id 
            WHERE c.student_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("i", $student['id']);
        $stmt->execute();
        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Overview data
$overviewData = [];
$overviewStats = ['total'=>0, 'paid'=>0, 'unpaid'=>0, 'mine'=>0];
if ($tab === 'overview') {
    $res = $conn->query("
        SELECT s.id, s.roll, s.name,
               COALESCE(SUM(c.amount), 0) AS total_collected,
               COUNT(DISTINCT c.admin_id) AS admin_count,
               COUNT(c.id) AS entries,
               COALESCE(SUM(CASE WHEN c.admin_id = $aid THEN c.amount END), 0) AS my_amount,
               COALESCE(SUM(CASE WHEN c.admin_id = $aid THEN 1 END), 0) AS my_times,
               GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS collectors
        FROM students s
        LEFT JOIN collections c ON c.student_id = s.id
        LEFT JOIN admins a ON c.admin_id = a.id
        GROUP BY s.id
        ORDER BY s.roll ASC
    ");
    while ($r = $res->fetch_assoc()) {
        $overviewData[] = $r;
        $overviewStats['total']++;
        if ($r['total_collected'] > 0) $overviewStats['paid']++;
        else $overviewStats['unpaid']++;
        if ($r['my_amount'] > 0) $overviewStats['mine']++;
    }
}
?>

<h2 class="mb-4">💰 Collect Money</h2>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab=='search'?'active':'' ?>" href="?tab=search">
            🔍 Search & Collect
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab=='overview'?'active':'' ?>" href="?tab=overview">
            📋 All Students Overview
            <span class="badge bg-primary"><?= $overviewStats['total'] ?></span>
        </a>
    </li>
</ul>

<?php if ($tab === 'search'): ?>

<!-- =================== SEARCH TAB =================== -->
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <input type="hidden" name="tab" value="search">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control form-control-lg" 
                       placeholder="🔍 Roll / Name লিখে Search করুন..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" required autofocus>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-lg w-100">🔍 Search</button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['search']) && !$student): ?>
    <div class="alert alert-warning">
        ❌ <strong>"<?= htmlspecialchars($_GET['search']) ?>"</strong> — এই student খুঁজে পাওয়া যায়নি।
    </div>
<?php endif; ?>

<?php if ($student): 
    $myTotal = 0;
    $totalAll = 0;
    foreach ($history as $h) {
        $totalAll += $h['amount'];
        if ($h['collector_id'] == $aid) $myTotal += $h['amount'];
    }
?>
    <!-- Total Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">এই Student থেকে মোট</h6>
                    <h2 class="mb-0">৳ <?= number_format($totalAll, 2) ?></h2>
                    <small><?= count($history) ?> entries (সব Admin)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">আপনার Collection</h6>
                    <h2 class="mb-0">৳ <?= number_format($myTotal, 2) ?></h2>
                    <small><?= count(array_filter($history, fn($h) => $h['collector_id'] == $aid)) ?> entries</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body text-center">
                    <h6 class="mb-1">অন্যান্য Admin</h6>
                    <h2 class="mb-0">৳ <?= number_format($totalAll - $myTotal, 2) ?></h2>
                    <small><?= count(array_filter($history, fn($h) => $h['collector_id'] != $aid)) ?> entries</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Info -->
    <div class="card stat-card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">🎓 Student Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><strong>Roll:</strong><br><span class="fs-5"><?= htmlspecialchars($student['roll']) ?></span></div>
                <div class="col-md-6"><strong>Name:</strong><br><span class="fs-5"><?= htmlspecialchars($student['name']) ?></span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-7">
            <div class="card stat-card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📋 Payment History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($history)): ?>
                        <div class="text-center py-5 text-muted">
                            <h1>📭</h1>
                            <p>এখনো কোনো payment করা হয়নি</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Collector</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $i=1; foreach ($history as $h): 
                                $mine = $h['collector_id'] == $aid;
                            ?>
                                <tr class="<?= $mine ? 'table-success' : '' ?>">
                                    <td><?= $i++ ?></td>
                                    <td><?= date('d-M-Y h:i A', strtotime($h['created_at'])) ?></td>
                                    <td class="fw-bold">৳ <?= number_format($h['amount'], 2) ?></td>
                                    <td>
                                        <?= htmlspecialchars($h['collector']) ?>
                                        <?php if ($mine): ?>
                                            <span class="badge bg-success">আপনি</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th colspan="2" class="text-end">সর্বমোট:</th>
                                    <th colspan="2">৳ <?= number_format($totalAll, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card stat-card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">💰 নতুন Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount (টাকা) *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="amount" class="form-control" 
                                       placeholder="0.00" required min="1" step="0.01" autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note (ঐচ্ছিক)</label>
                            <input type="text" name="note" class="form-control" placeholder="যেমন: প্রথম কিস্তি">
                        </div>
                        <button name="save" class="btn btn-success btn-lg w-100">
                            ✅ Collection Save করুন
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php else: ?>

<!-- =================== OVERVIEW TAB =================== -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body text-center">
                <h6>মোট Students</h6>
                <h2 class="mb-0"><?= $overviewStats['total'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body text-center">
                <h6>Paid (কেউ collect করেছে)</h6>
                <h2 class="mb-0"><?= $overviewStats['paid'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Unpaid (কেউ দেয়নি)</h6>
                <h2 class="mb-0"><?= $overviewStats['unpaid'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body text-center">
                <h6>আপনি collect করেছেন</h6>
                <h2 class="mb-0"><?= $overviewStats['mine'] ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Search within overview -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <input type="text" id="tableSearch" class="form-control form-control-lg" 
               placeholder="🔍 এই list-এ খুঁজুন (Roll / Name)...">
    </div>
</div>

<div class="card stat-card">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 All Students — Collected Status</h5>
        <small>💡 Green = আপনি collect করেছেন, Yellow = অন্য Admin, Red = Unpaid</small>
    </div>
    <div class="card-body p-0">
        <table class="table-app" id="overviewTable">
            <thead>
    <tr>
        <th>#</th>
        <th>Roll</th>
        <th>Name</th>
        <th>মোট Collected</th>
        <th>Collectors</th>
        <th>আপনার</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
            <tbody>
            <?php if (empty($overviewData)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">কোনো student নেই</td></tr>
            <?php else: $i=1; foreach ($overviewData as $s): 
                $status = 'Unpaid';
                $badge = 'danger';
                $rowClass = '';
                
                if ($s['my_amount'] > 0 && $s['total_collected'] == $s['my_amount']) {
                    $status = 'Paid (আপনি)';
                    $badge = 'success';
                  $rowClass = 'row-paid';
                } elseif ($s['my_amount'] > 0) {
                    $status = 'Partial (আপনি + অন্য)';
                    $badge = 'info';
                    $rowClass = 'row-partial';
                } elseif ($s['total_collected'] > 0) {
                    $status = 'Paid (অন্য Admin)';
                    $badge = 'warning';
                    $rowClass = 'row-other';
                } else {
                    $status = 'Unpaid';
                    $badge = 'danger';
                   $rowClass = 'row-unpaid';
                }
            ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= $i++ ?></td><td>
    <a href="student-history.php?id=<?= $s['id'] ?>" class="text-decoration-none">
        <strong><?= htmlspecialchars($s['roll']) ?></strong>
    </a>
</td>
                    >
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td>
                        <?php if ($s['total_collected'] > 0): ?>
                            <strong class="text-success">৳ <?= number_format($s['total_collected'], 2) ?></strong>
                            <br><small class="text-muted"><?= $s['entries'] ?> entries</small>
                        <?php else: ?>
                            <span class="text-muted">৳ 0.00</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['collectors']): ?>
                            <small><?= htmlspecialchars($s['collectors']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['my_amount'] > 0): ?>
                            <strong>৳ <?= number_format($s['my_amount'], 2) ?></strong>
                            <br><small><?= $s['my_times'] ?> বার</small>
                        <?php else: ?>
                            <span class="text-muted">৳ 0.00</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-app <?= $badge ?>">
    <?php if ($badge == 'success'): ?><i class="bi bi-check-circle-fill"></i>
    <?php elseif ($badge == 'warning'): ?><i class="bi bi-exclamation-triangle-fill"></i>
    <?php elseif ($badge == 'danger'): ?><i class="bi bi-x-circle-fill"></i>
    <?php else: ?><i class="bi bi-info-circle-fill"></i>
    <?php endif; ?>
    <?= $status ?>
</span>
                    </td>
                    <td>
                        <a href="?tab=search&search=<?= urlencode($s['roll']) ?>" 
                           class="btn btn-sm btn-primary">
                            💰 Collect
                        </a>
                        <a href="student-history.php?id=<?= $s['id'] ?>" 
   class="btn btn-sm btn-info" title="View History">
    👁️
</a>
<a href="?tab=search&search=<?= urlencode($s['roll']) ?>" 
   class="btn btn-sm btn-primary">
    <i class="bi bi-cash-coin"></i> Collect
</a>             </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Client-side table search
document.getElementById('tableSearch')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#overviewTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>