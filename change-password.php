<?php
require_once 'includes/auth.php';

$msg = '';
$type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
        $msg = "❌ সব field পূরণ করুন";
        $type = 'danger';
    } elseif (strlen($newPass) < 6) {
        $msg = "❌ নতুন password কমপক্ষে ৬ অক্ষরের হতে হবে";
        $type = 'danger';
    } elseif ($newPass !== $confirmPass) {
        $msg = "❌ New password এবং Confirm password মিলছে না";
        $type = 'danger';
    } elseif ($oldPass === $newPass) {
        $msg = "⚠️ নতুন password পুরোনোটার মতোই";
        $type = 'warning';
    } else {
        // Verify old password
        $uid = $_SESSION['admin_id'];
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($oldPass, $row['password'])) {
            $msg = "❌ পুরোনো password ভুল";
            $type = 'danger';
        } else {
            // Update password
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $upd->bind_param("si", $newHash, $uid);
            
            if ($upd->execute()) {
                logActivity($conn, $uid, 'password_change', 'Password changed');
                $msg = "✅ Password সফলভাবে পরিবর্তন হয়েছে!";
                $type = 'success';
            } else {
                $msg = "❌ Password পরিবর্তন করতে সমস্যা";
                $type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — Event Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .pwd-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .pwd-icon {
            font-size: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card pwd-card">
                <div class="card-body p-4">
                    <div class="pwd-icon">🔐</div>
                    <h3 class="text-center mb-1">Change Password</h3>
                    <p class="text-center text-muted mb-4">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($_SESSION['name']) ?>
                        <span class="badge bg-<?= $_SESSION['role']=='super'?'danger':'info' ?>">
                            <?= ucfirst($_SESSION['role']) ?>
                        </span>
                    </p>
                    
                    <?php if ($msg): ?>
                        <div class="alert alert-<?= $type ?> alert-dismissible fade show">
                            <?= $msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label fw-bold">পুরোনো Password</label>
                            <div class="input-group">
                                <span class="input-group-text">🔑</span>
                                <input type="password" name="old_password" class="form-control" required autofocus>
                                <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="old_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">নতুন Password</label>
                            <div class="input-group">
                                <span class="input-group-text">🆕</span>
                                <input type="password" name="new_password" id="new_password" class="form-control" 
                                       required minlength="6">
                                <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="new_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="strengthBar" class="progress-bar" style="width: 0%"></div>
                            </div>
                            <small id="strengthText" class="text-muted">কমপক্ষে ৬ অক্ষর</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirm নতুন Password</label>
                            <div class="input-group">
                                <span class="input-group-text">✅</span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small id="matchText"></small>
                        </div>
                        
                        <button class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="bi bi-shield-lock-fill"></i> Password পরিবর্তন করুন
                        </button>
                        
                        <?php if ($_SESSION['role'] === 'super'): ?>
                            <a href="super-admin/dashboard.php" class="btn btn-secondary w-100">
                                ← Dashboard-এ ফিরুন
                            </a>
                        <?php else: ?>
                            <a href="admin/dashboard.php" class="btn btn-secondary w-100">
                                ← Dashboard-এ ফিরুন
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = document.querySelector(`input[name="${this.dataset.target}"]`);
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
});

// Password strength
const newPassInput = document.getElementById('new_password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

newPassInput?.addEventListener('input', function() {
    const val = this.value;
    let score = 0;
    
    if (val.length >= 6) score += 25;
    if (val.length >= 10) score += 15;
    if (/[a-z]/.test(val)) score += 15;
    if (/[A-Z]/.test(val)) score += 15;
    if (/[0-9]/.test(val)) score += 15;
    if (/[^A-Za-z0-9]/.test(val)) score += 15;
    
    strengthBar.style.width = score + '%';
    
    if (score < 40) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'দুর্বল';
        strengthText.className = 'text-danger';
    } else if (score < 70) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'মাঝারি';
        strengthText.className = 'text-warning';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'শক্তিশালী ✅';
        strengthText.className = 'text-success';
    }
});

// Match check
const confirmInput = document.getElementById('confirm_password');
const matchText = document.getElementById('matchText');

confirmInput?.addEventListener('input', function() {
    if (this.value === newPassInput.value && this.value.length > 0) {
        matchText.textContent = '✅ Password মিলছে';
        matchText.className = 'text-success';
    } else if (this.value.length > 0) {
        matchText.textContent = '❌ Password মিলছে না';
        matchText.className = 'text-danger';
    } else {
        matchText.textContent = '';
    }
});
</script>
</body>
</html>