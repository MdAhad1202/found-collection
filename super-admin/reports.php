<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$tab = $_GET['tab'] ?? 'admin';

// Admin-wise summary
$adminSummary = $conn->query("
    SELECT a.id, a.name,
           COUNT(DISTINCT c.student_id) AS students,
           COUNT(c.id) AS entries,
           COALESCE(SUM(c.amount), 0) AS total
    FROM admins a
    LEFT JOIN collections c ON c.admin_id = a.id
    WHERE a.role='admin'
    GROUP BY a.id
    ORDER BY total DESC
");

// Student-wise summary
$studentSummary = $conn->query("
    SELECT s.id, s.roll, s.name,
           COUNT(c.id) AS times,
           COALESCE(SUM(c.amount), 0) AS total,
           MAX(c.created_at) AS last_payment
    FROM students s
    LEFT JOIN collections c ON c.student_id = s.id
    GROUP BY s.id
    ORDER BY s.roll ASC
");

// Daily summary
$dailySummary = $conn->query("
    SELECT DATE(created_at) AS day,
           COUNT(*) AS entries,
           COUNT(DISTINCT student_id) AS students,
           SUM(amount) AS total
    FROM collections
    GROUP BY DATE(created_at)
    ORDER BY day DESC
    LIMIT 30
");
?>

<h2 class="mb-4">📊 Reports</h2>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab=='admin'?'active':'' ?>" href="?tab=admin">
            👥 Admin-wise
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab=='student'?'active':'' ?>" href="?tab=student">
            🎓 Student-wise
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab=='daily'?'active':'' ?>" href="?tab=daily">
            📅 Daily
        </a>
    </li>
</ul>

<?php if ($tab === 'admin'): ?>
    <div class="d-flex justify-content-end mb-2">
        <a href="download-admin-report.php" class="btn btn-sm btn-success">📥 Download</a>
    </div>
    <div class="card stat-card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Collector (Admin)</th>
                        <th class="text-end">Students</th>
                        <th class="text-end">Entries</th>
                        <th class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; $grand=0; while ($a = $adminSummary->fetch_assoc()): $grand += $a['total']; ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                        <td class="text-end"><?= $a['students'] ?> জন</td>
                        <td class="text-end"><?= $a['entries'] ?></td>
                        <td class="text-end text-success fw-bold">৳ <?= number_format($a['total'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-success">
                        <th colspan="4" class="text-end">সর্বমোট:</th>
                        <th class="text-end">৳ <?= number_format($grand, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'student'): ?>
    <div class="d-flex justify-content-end mb-2">
        <a href="download-student-report.php" class="btn btn-sm btn-success">📥 Download</a>
    </div>
    <div class="card stat-card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Roll</th>
                        <th>Name</th>
                        <th class="text-end">Times</th>
                        <th class="text-end">Total Paid</th>
                        <th>Last Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($s = $studentSummary->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($s['roll']) ?></strong></td>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td class="text-end"><?= $s['times'] ?></td>
                        <td class="text-end <?= $s['total']>0?'text-success fw-bold':'text-muted' ?>">
                            ৳ <?= number_format($s['total'], 2) ?>
                        </td>
                        <td>
                            <?= $s['last_payment'] ? date('d-M-Y', strtotime($s['last_payment'])) : '—' ?>
                        </td>
                        <td>
                            <?php if ($s['total'] > 0): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unpaid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <div class="card stat-card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th class="text-end">Students</th>
                        <th class="text-end">Entries</th>
                        <th class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($dailySummary->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">কোনো entry নেই</td></tr>
                <?php else: $i=1; while ($d = $dailySummary->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= date('d-M-Y (D)', strtotime($d['day'])) ?></strong></td>
                        <td class="text-end"><?= $d['students'] ?></td>
                        <td class="text-end"><?= $d['entries'] ?></td>
                        <td class="text-end text-success fw-bold">৳ <?= number_format($d['total'], 2) ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>