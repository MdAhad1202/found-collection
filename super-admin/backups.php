<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$backupRoot = 'C:\backups\event-collection';
$backups = [];

if (is_dir($backupRoot)) {
    $dirs = scandir($backupRoot);
    foreach ($dirs as $d) {
        if ($d === '.' || $d === '..') continue;
        $path = $backupRoot . '\\' . $d;
        if (is_dir($path)) {
            $size = 0;
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($files as $f) $size += $f->getSize();
            
            $backups[] = [
                'name' => $d,
                'path' => $path,
                'size' => $size,
                'time' => filemtime($path),
            ];
        }
    }
    // Sort by newest first
    usort($backups, fn($a, $b) => $b['time'] - $a['time']);
}
?>

<div class="page-header">
    <h2 class="page-title">💾 Backups</h2>
    <div>
        <span class="badge-app primary"><?= count($backups) ?> Backups</span>
    </div>
</div>

<div class="alert alert-info">
    <strong>📁 Location:</strong> <code><?= $backupRoot ?></code><br>
    <strong>🔄 Auto:</strong> প্রতিদিন রাত ১১টায় (Windows Task Scheduler)<br>
    <strong>📦 Contents:</strong> Database + Uploads + Config + Source Code
</div>

<?php if (empty($backups)): ?>
    <div class="card-app">
        <div class="card-body text-center py-5">
            <h1>📭</h1>
            <p class="text-muted">কোনো backup নেই। CMD-এ <code>backup.bat</code> চালান।</p>
        </div>
    </div>
<?php else: ?>
    <div class="card-app">
        <div class="card-body p-0">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>Size</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; foreach ($backups as $b): 
                    $sizeMB = round($b['size'] / 1024 / 1024, 2);
                    $isRecent = (time() - $b['time']) < 86400 * 2; // 2 days
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <strong><?= date('d-M-Y', $b['time']) ?></strong><br>
                            <small class="text-muted"><?= date('h:i A', $b['time']) ?></small>
                        </td>
                        <td><?= $sizeMB ?> MB</td>
                        <td><code class="small"><?= $b['name'] ?></code></td>
                        <td>
                            <?php if ($isRecent): ?>
                                <span class="badge-app success">
                                    <i class="bi bi-check-circle"></i> Recent
                                </span>
                            <?php else: ?>
                                <span class="badge-app warning">
                                    <i class="bi bi-clock"></i> Old
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>