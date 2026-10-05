<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

// Stats
$total = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM collections")->fetch_assoc()['t'];
$paid = $conn->query("SELECT COUNT(DISTINCT student_id) AS c FROM collections")->fetch_assoc()['c'];
$all_students = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$pending = $all_students - $paid;
$today = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM collections WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['t'];
$today_count = $conn->query("SELECT COUNT(*) AS c FROM collections WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
?>

<h2 class="mb-4">📊 Super Admin Dashboard</h2>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">Total Collection</h6>
                        <h3 class="mb-0">৳ <?= number_format($total, 2) ?></h3>
                    </div>
                    <div class="icon">💰</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">Paid Students</h6>
                        <h3 class="mb-0"><?= $paid ?> / <?= $all_students ?></h3>
                    </div>
                    <div class="icon">✅</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">Unpaid Students</h6>
                        <h3 class="mb-0"><?= $pending ?></h3>
                    </div>
                    <div class="icon">⚠️</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-1">Today's Collection</h6>
                        <h3 class="mb-0">৳ <?= number_format($today, 2) ?></h3>
                        <small><?= $today_count ?> entries</small>
                    </div>
                    <div class="icon">📅</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">👥 Admin-wise Summary</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Collector</th>
                    <th>Students</th>
                    <th>Entries</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = $conn->query("
                SELECT a.id, a.name, 
                       COUNT(DISTINCT c.student_id) AS students,
                       COUNT(c.id) AS entries,
                       COALESCE(SUM(c.amount),0) AS total
                FROM admins a
                LEFT JOIN collections c ON c.admin_id = a.id
                WHERE a.role='admin'
                GROUP BY a.id
            ");
            if ($res->num_rows === 0):
            ?>
                <tr><td colspan="4" class="text-center text-muted py-4">
                    এখনো কোনো Admin যোগ করা হয়নি
                </td></tr>
            <?php else: while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= $row['students'] ?> জন</td>
                    <td><?= $row['entries'] ?></td>
                    <td class="text-end">৳ <?= number_format($row['total'], 2) ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">📈 Last 7 Days Collection Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="80"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">🥧 Admin-wise Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="adminChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
// Chart data
$trendRes = $conn->query("
    SELECT DATE(created_at) AS day, SUM(amount) AS total
    FROM collections
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendData[$d] = 0;
}
while ($t = $trendRes->fetch_assoc()) {
    $trendData[$t['day']] = (float)$t['total'];
}

$adminDist = $conn->query("
    SELECT a.name, COALESCE(SUM(c.amount), 0) AS total
    FROM admins a
    LEFT JOIN collections c ON c.admin_id = a.id
    WHERE a.role='admin'
    GROUP BY a.id
    HAVING total > 0
    ORDER BY total DESC
");
$adminLabels = [];
$adminValues = [];
while ($a = $adminDist->fetch_assoc()) {
    $adminLabels[] = $a['name'];
    $adminValues[] = (float)$a['total'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d)), array_keys($trendData))) ?>,
        datasets: [{
            label: 'Collection (৳)',
            data: <?= json_encode(array_values($trendData)) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#3498db',
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => '৳ ' + v.toLocaleString()
                }
            }
        }
    }
});

// Admin distribution chart
const adminCtx = document.getElementById('adminChart').getContext('2d');
new Chart(adminCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($adminLabels) ?>,
        datasets: [{
            data: <?= json_encode($adminValues) ?>,
            backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>