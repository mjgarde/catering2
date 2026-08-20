<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connection.php';
define('ENC_KEY', 'YourSecretKey1234567890abcdef12');
include 'classes/StaffAuth.php';
include 'classes/AdminAuth.php';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin/dashboard.php');
    exit();
}
if (!empty($_SESSION['staff_logged_in'])) {
    header('Location: staff/dashboard.php');
    exit();
}

function get_real_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ip === '::1') return '127.0.0.1';
    if (str_starts_with($ip, '::ffff:')) {
        return substr($ip, 7);
    }
    return $ip;
}

function is_safe_input($val) {
    $blocked = ["'", '"', ';', '--', '#', '/*', '*/', 'SELECT', 'INSERT', 'UPDATE',
                'DELETE', 'DROP', 'UNION', 'OR ', 'AND ', '<script', '</script',
                '<', '>', '\\', '/', '=', '%', '&', '|', '`', 'EXEC', 'CAST',
                'CHAR(', 'alert(', 'onerror', 'onload'];
    $upper = strtoupper($val);
    foreach ($blocked as $b) {
        if (str_contains($upper, strtoupper($b))) return false;
    }
    return true;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!is_safe_input($username) || !is_safe_input($password)) {
        $error_message = 'Invalid characters detected in input.';
    } elseif (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $admin_auth = new AdminAuth($conn);
        if ($admin_auth->login($username, $password)) {
            header('Location: admin/dashboard.php');
            exit();
        }

        $staff_auth = new StaffAuth($conn);
        if ($staff_auth->login($username, $password)) {
            header('Location: staff/dashboard.php');
            exit();
        }

        $error_message = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font/css/all.min.css">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                        <h1 class="h4 fw-bold">Sign In</h1>
                    </div>

                    <?php if (isset($_GET['reset'])): ?>
                    <div class="alert alert-success py-2 small">
                        <i class="fas fa-check-circle me-1"></i> Password reset successfully. You can now log in.
                    </div>
                    <?php elseif (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0"
                                    name="username" placeholder="Enter username"
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                    maxlength="50" pattern="[a-zA-Z0-9_]+"
                                    title="Only letters, numbers, and underscores allowed"
                                    autocomplete="off" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0"
                                    name="password" placeholder="Enter password"
                                    maxlength="72" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/font/js/all.min.js"></script>
<script>
const blocked = ["'", '"', ';', '--', '<', '>', '\\', '=', '`', '|', '&', '%'];
document.querySelectorAll('input[type=text], input[type=password]').forEach(input => {
    input.addEventListener('input', function () {
        blocked.forEach(c => { this.value = this.value.split(c).join(''); });
    });
    input.addEventListener('paste', function (e) {
        e.preventDefault();
        let text = (e.clipboardData || window.clipboardData).getData('text');
        blocked.forEach(c => { text = text.split(c).join(''); });
        this.value += text;
    });
});
</script>
</body>
</html>