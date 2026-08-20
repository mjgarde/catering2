<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connection.php';

define('ENC_KEY', 'YourSecretKey1234567890abcdef12');
define('ENC_METHOD', 'AES-256-CBC');

function enc($data) {
    if ($data === null || $data === '') return '';
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, ENC_METHOD, ENC_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!is_safe_input($fullname) || !is_safe_input($address) || !is_safe_input($username) || !is_safe_input($password)) {
        $error_message = 'Invalid characters detected in input.';
    } elseif (empty($fullname) || empty($address) || empty($gender) || empty($birthday) || empty($contact_number) || empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $error_message = 'Invalid gender selection.';
    } elseif (strlen($contact_number) < 10) {
        $error_message = 'Contact number must be at least 10 digits.';
    } elseif (strlen($username) < 3) {
        $error_message = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT id FROM customer_info WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error_message = 'Username already exists. Please choose another.';
        } else {
            $e_fullname = enc($fullname);
            $e_address = enc($address);
            $e_contact = enc($contact_number);
            $e_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO customer_info (fullname, address, gender, birthday, contact_number, username, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $e_fullname, $e_address, $gender, $birthday, $e_contact, $username, $e_password);
            if ($stmt->execute()) {
                $success_message = 'Registration successful! You can now login.';
                echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 2000);</script>';
            } else {
                $error_message = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .register-container {
            max-width: 600px;
            margin: 50px auto;
        }
        .card { border-radius: 15px; }
    </style>
</head>
<body>
<div class="container">
    <div class="register-container">
        <div class="card shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-user-plus fa-4x text-primary mb-3"></i>
                    <h2 class="h4 fw-bold">Create Customer Account</h2>
                    <p class="text-muted">Register to start renting equipment</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" name="fullname" placeholder="Enter full name" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-home text-muted"></i></span>
                            <textarea class="form-control" name="address" rows="2" placeholder="Enter address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="Male" <?= ($_POST['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($_POST['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($_POST['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Birthday <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-calendar text-muted"></i></span>
                                <input type="date" class="form-control" name="birthday" value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                            <input type="text" class="form-control" name="contact_number" placeholder="Enter contact number" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>" required>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-circle text-muted"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Choose username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-check text-muted"></i></span>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="register" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                        <a href="login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/font/js/all.min.js"></script>
<script>
const blocked = ["'", '"', ';', '--', '<', '>', '\\', '=', '`', '|', '&', '%'];
document.querySelectorAll('input[type=text], input[type=password], textarea').forEach(input => {
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