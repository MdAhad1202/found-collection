<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$msg = '';
$msg_type = 'info';

// Add Admin
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (strlen($password) < 6) {
        $msg = "❌ Password কমপক্ষে ৬ অক্ষরের হতে হবে";
        $msg_type = 'danger';
    } else {
        // Check username
        $check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = "❌ Username '{$username}' already আছে";
            $msg_type = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (name, username, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $name, $username, $hash);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['admin_id'], 'admin_add', "Added: $username");
                $msg = "✅ Admin '{$name}' যোগ হয়েছে";
                $msg_type = 'success';
            } else {
                $msg = "❌ Database error";
                $msg_type = 'danger';
            }
        }
    }
}

// Delete Admin
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $check = $conn->query("SELECT username FROM admins WHERE id=$id AND role='admin'")->fetch_assoc();
    if ($check) {
        $conn->query("DELETE FROM admins WHERE id=$id AND role='admin'");
        logActivity($conn, $_SESSION['admin_id'], 'admin_delete', "Deleted: {$check['username']}");
        $msg = "🗑️ Admin মুছে ফেলা হয়েছে";
        $msg_type = 'success';
    }
}

// Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE admins SET status = 1 - status WHERE id=$id AND role='admin'");
    $msg = "🔄 Status পরিবর্তন হয়েছে";
    $msg_type = 'info';
}

// Reset password form
if (isset($_POST['reset'])) {
    $id = (int)$_POST['reset_id'];
    $newpass = $_POST['new_password'];
    if (strlen($newpass) >= 6) {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ? AND role='admin'");
        $stmt->bind_param("si", $hash, $id);
        $stmt->execute();
        $msg = "🔑 Password reset হয়েছে";
        $msg_type = 'success';
    } else {
        $msg = "❌ Password কমপক্ষে ৬ অক্ষরের";
        $msg_type = 'danger';
    }
}
?>

<h2 class="mb-4">👥 Admin Management</h2>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- Add Admin Form -->
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">➕ নতুন Admin</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">নাম</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">সর্বনিম্ন ৬ অক্ষর</small>
                    </div>
                    <button name="add" class="btn btn-primary w-100">➕ Add Admin</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Admin List -->
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📋 Admin List</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM admins ORDER BY role='super' DESC, id");
                    $i = 1;
                    while ($r = $res->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($r['username']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $r['role']=='super'?'danger':'info' ?>">
                                    <?= ucfirst($r['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['status']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['role'] !== 'super'): ?>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?= $r['id'] ?>">
                                        🔑
                                    </button>
                                    <a href="?toggle=<?= $r['id'] ?>" class="btn btn-sm btn-secondary" title="Toggle Status">🔄</a>
                                    <a href="?del=<?= $r['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('নিশ্চিত? Admin \'<?= htmlspecialchars($r['name']) ?>\' মুছে ফেলবেন?')">🗑️</a>
                                    
                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetModal<?= $r['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">🔑 Reset Password — <?= htmlspecialchars($r['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="reset_id" value="<?= $r['id'] ?>">
                                                        <label class="form-label">নতুন Password</label>
                                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button name="reset" class="btn btn-primary">Reset</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>