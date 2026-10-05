<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Already logged in? Redirect to correct dashboard
if (isset($_SESSION['admin_id'])) {
    if ($_SESSION['role'] === 'super') {
        redirect('/event-collection/super-admin/dashboard.php');
    } else {
        redirect('/event-collection/admin/dashboard.php');
    }
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = "⏱️ সময় শেষ — আবার login করুন";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
logActivity($conn, $row['id'], 'login', "Logged in from IP: $ip");
            
            if ($row['role'] === 'super') {
                redirect('/event-collection/super-admin/dashboard.php');
            } else {
                redirect('/event-collection/admin/dashboard.php');
            }
        } else {
            $error = "❌ ভুল Username বা Password";
        }
    } else {
        $error = "⚠️ Username এবং Password দিন";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Event Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .login-icon {
            font-size: 60px;
            text-align: center;
            margin-bottom: 10px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="login-card">
                <div class="login-icon">📊</div>
                <h3 class="text-center mb-1">Event Collection</h3>
                <p class="text-center text-muted mb-4">Admin Login</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <button class="btn btn-login w-100 btn-lg">🔐 Login</button>
                </form>
                
                <p class="text-center text-muted mt-4 mb-0">
                    <small>Default: <code>superadmin</code> / <code>admin123</code></small>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>