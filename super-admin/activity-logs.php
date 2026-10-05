<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

// Filter params
$adminFilter = (int)($_GET['admin'] ?? 0);
$actionFilter = trim($_GET['action'] ?? '');
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$search = trim($_GET['search'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build WHERE
$where = ["DATE(a.created_at) BETWEEN ? AND ?"];
$params = [$from, $to];
$types = 'ss';

if ($adminFilter > 0) {
    $where[] = "a.admin_id = ?";
    $params[] = $adminFilter;
    $types .= 'i';
}
if ($actionFilter) {
    $where[] = "a.action = ?";
    $params[] = $actionFilter;
    $types .= 's';
}
if ($search) {
    $where[] = "(a.details LIKE ? OR ad.name LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
    $types .= 'ss';
}

$whereSQL = "WHERE " . implode(" AND ", $where);

// Count total
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM activity_logs a 
    LEFT JOIN admins ad ON a.admin_id = ad.id 
    $whereSQL
");
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Get data
$dataParams = array_merge($params, [$perPage, $offset]);
$dataTypes = $types . 'ii';

$dataStmt = $conn->prepare("
    SELECT a.*, ad.name AS admin_name, ad.role AS admin_role
    FROM activity_logs a 
    LEFT JOIN admins ad ON a.admin_id = ad.id 
    $whereSQL
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$logs = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Today's stats
$todayStats = $conn->query("
    SELECT 
        COUNT(*) AS total,
        COUNT(DISTINCT admin_id) AS admins
    FROM activity_logs 
    WHERE DATE(created_at) = CURDATE()
")->fetch_assoc();

// Admin list for filter
$adminsList = $conn->query("SELECT id, name FROM admins ORDER BY name");

// Unique action types
$actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");

// Action type colors & icons
function getActionBadge($action) {
    $map = [
        'login'             => ['success', 'box-arrow-in-right', 'Login'],
        'logout'            => ['secondary', 'box-arrow-right', 'Logout'],
        'admin_add'         => ['primary', 'person-plus', 'Admin Add'],
        'admin_delete'      => ['danger', 'person-x', 'Admin Delete'],
        'excel_upload'      => ['info', 'cloud-upload', 'Excel Upload'],
        'collection_add'    => ['success', 'cash-coin', 'Collection Added'],
        'collection_edit'   => ['warning', 'pencil', 'Collection Edit'],
        'collection_delete' => ['danger', 'trash', 'Collection Delete'],
        'password_change'   => ['dark', 'shield-lock', 'Password Change'],
    ];
    return $map[$action] ?? ['secondary', 'activity', ucfirst(str_replace('_', ' ', $action))];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📜 Activity Log</h2>
    <div>
        <span class="badge bg-primary">Total: <?= $totalRows ?></span>
        <span class="badge bg-info">Today: <?= $todayStats['total'] ?></span>
    </div>
</div>

<!-- Filter -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <label class="form-label small">Admin</label>
                <select name="admin" class="form-select form-select-sm">
                    <option value="0">সব</option>
                    <?php $adminsList->data_seek(0); while ($a = $adminsList->fetch_assoc()): ?>
                        <option value="<?= $a['id'] ?>" <?= $adminFilter==$a['id']?'selected':'' ?>>
                            <?= htmlspecialchars($a['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">সব</option>
                    <?php $actions->data_seek(0); while ($a = $actions->fetch_assoc()): ?>
                        <option value="<?= $a['action'] ?>" <?= $actionFilter==$a['action']?'selected':'' ?>>
                            <?= htmlspecialchars(str_replace('_', ' ', $a['action'])) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="from" value="<?= $from ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="to" value="<?= $to ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="form-control form-control-sm" placeholder="Details/Name">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button class="btn btn-sm btn-primary flex-grow-1">🔍 Filter</button>
                <a href="activity-logs.php" class="btn btn-sm btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card stat-card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 160px;">Date & Time</th>
                    <th style="width: 150px;">Admin</th>
                    <th style="width: 180px;">Action</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 40px;"></i>
                        <p class="mt-2">এই filter-এ কোনো log নেই</p>
                    </td>
                </tr>
            <?php else: 
                $startNum = ($page - 1) * $perPage + 1;
                foreach ($logs as $log): 
                    [$color, $icon, $label] = getActionBadge($log['action']);
            ?>
                <tr>
                    <td class="text-muted"><?= $startNum++ ?></td>
                    <td>
                        <small>
                            <strong><?= date('d-M-Y', strtotime($log['created_at'])) ?></strong><br>
                            <span class="text-muted"><?= date('h:i:s A', strtotime($log['created_at'])) ?></span>
                        </small>
                    </td>
                    <td>
                        <?php if ($log['admin_name']): ?>
                            <strong><?= htmlspecialchars($log['admin_name']) ?></strong>
                            <br>
                            <small class="badge bg-<?= $log['admin_role']=='super'?'danger':'info' ?>">
                                <?= ucfirst($log['admin_role']) ?>
                            </small>
                        <?php else: ?>
                            <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $color ?>">
                            <i class="bi bi-<?= $icon ?>"></i> <?= $label ?>
                        </span>
                    </td>
                    <td>
                        <small><?= htmlspecialchars($log['details'] ?? '—') ?></small>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    ← Prev
                </a>
            </li>
        <?php endif; ?>
        
        <?php 
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a></li>
            <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    Next →
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<p class="text-center text-muted">
    Page <?= $page ?> of <?= $totalPages ?> | 
    Showing <?= ($page-1)*$perPage+1 ?> - <?= min($page*$perPage, $totalRows) ?> of <?= $totalRows ?>
</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>