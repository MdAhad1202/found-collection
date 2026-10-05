<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';
$type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {
    $file = $_FILES['excel'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['xlsx', 'xls', 'csv'];
    
    if (!in_array($ext, $allowed)) {
        $message = "❌ শুধু .xlsx, .xls, .csv file allow করা হয়";
        $type = 'danger';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $message = "❌ File size 5MB-এর বেশি";
        $type = 'danger';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "❌ Upload error: " . $file['error'];
        $type = 'danger';
    } else {
        $dir = __DIR__ . '/../uploads/students/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $newName = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
        $path = $dir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $path)) {
            try {
                $sheet = IOFactory::load($path)->getActiveSheet()->toArray();
                $ins = $skip = $tot = 0;
                
                foreach ($sheet as $i => $row) {
                    if ($i === 0) continue; // skip header
                    
                    $roll = trim($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    if (!$roll || !$name) continue;
                    
                    $cls  = trim($row[2] ?? '');
                    $ph   = trim($row[3] ?? '');
                    $tot++;
                    
                    $stmt = $conn->prepare("INSERT IGNORE INTO students (roll, name, class, phone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $roll, $name, $cls, $ph);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) $ins++;
                    else $skip++;
                }
                
                // Save upload record
                $stmt = $conn->prepare("INSERT INTO uploads (file_name, original_name, file_size, uploaded_by, total_rows, inserted_rows, skipped_rows) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiiiii", $newName, $file['name'], $file['size'], $_SESSION['admin_id'], $tot, $ins, $skip);
                $stmt->execute();
                
                logActivity($conn, $_SESSION['admin_id'], 'excel_upload', "File: $newName, In: $ins, Skip: $skip");
                
                $message = "✅ Upload successful!<br>
                            📁 File: <strong>$newName</strong><br>
                            📊 Total: $tot<br>
                            ✅ Inserted: $ins<br>
                            ⚠️ Skipped (duplicate): $skip";
                $type = 'success';
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
                $type = 'danger';
            }
        } else {
            $message = "❌ File save করতে সমস্যা";
            $type = 'danger';
        }
    }
}
?>

<h2 class="mb-4">📤 Excel Upload — Student List</h2>

<div class="alert alert-info">
    <strong>📋 Excel Format:</strong>
   <code>Roll | Name</code> (Class, Phone optional)
    <br>
    <small>Max 5MB, শুধু .xlsx / .xls / .csv</small>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $type ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-2">
                <div class="col-md-8">
                    <input type="file" name="excel" accept=".xlsx,.xls,.csv" required class="form-control">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">📤 Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-3">📁 Recent Uploads</h4>
<div class="card stat-card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Saved As</th>
                    <th>Inserted</th>
                    <th>Skipped</th>
                    <th>By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = $conn->query("
                SELECT u.*, a.name AS uploader 
                FROM uploads u 
                LEFT JOIN admins a ON u.uploaded_by = a.id 
                ORDER BY u.id DESC LIMIT 20
            ");
            if ($res->num_rows === 0):
            ?>
                <tr><td colspan="8" class="text-center text-muted py-4">এখনো কোনো upload হয়নি</td></tr>
            <?php else: $i=1; while ($u = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($u['original_name']) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($u['file_name']) ?></small></td>
                    <td><span class="badge bg-success"><?= $u['inserted_rows'] ?></span></td>
                    <td><span class="badge bg-warning"><?= $u['skipped_rows'] ?></span></td>
                    <td><?= htmlspecialchars($u['uploader'] ?? '—') ?></td>
                    <td><?= date('d-M-Y H:i', strtotime($u['created_at'])) ?></td>
                    <td>
                        <a href="download-uploaded.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">📥</a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>