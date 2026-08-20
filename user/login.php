<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connection.php';
// include 'classes/CustomerAuth.php';

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

$login_error = '';
$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!is_safe_input($username) || !is_safe_input($password)) {
            $login_error = 'Invalid characters detected.';
        } elseif (empty($username) || empty($password)) {
            $login_error = 'Please enter both username and password.';
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

            $customer_auth = new CustomerAuth($conn);
            if ($customer_auth->login($username, $password)) {
                header('Location: user/dashboard.php');
                exit();
            }

            $login_error = 'Invalid username or password.';
        }
    }

    if (isset($_POST['register'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $birthday = $_POST['birthday'] ?? '';
        $contact_number = trim($_POST['contact_number'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!is_safe_input($fullname) || !is_safe_input($address) || !is_safe_input($username) || !is_safe_input($password)) {
            $register_error = 'Invalid characters detected.';
        } elseif (empty($fullname) || empty($address) || empty($gender) || empty($birthday) || empty($contact_number) || empty($username) || empty($password)) {
            $register_error = 'Please fill in all fields.';
        } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
            $register_error = 'Invalid gender selection.';
        } elseif (strlen($contact_number) < 10) {
            $register_error = 'Contact number must be at least 10 digits.';
        } elseif (strlen($username) < 3) {
            $register_error = 'Username must be at least 3 characters.';
        } elseif (strlen($password) < 6) {
            $register_error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Passwords do not match.';
        } else {
            $check = $conn->prepare("SELECT id FROM customer_info WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $register_error = 'Username already exists.';
            } else {
                $e_fullname = enc($fullname);
                $e_address = enc($address);
                $e_contact = enc($contact_number);
                $e_password = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $conn->prepare("INSERT INTO customer_info (fullname, address, gender, birthday, contact_number, username, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $e_fullname, $e_address, $gender, $birthday, $e_contact, $username, $e_password);
                if ($stmt->execute()) {
                    $register_success = 'Registration successful! You can now login.';
                } else {
                    $register_error = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

// ---------------- FETCH PACKAGES ----------------
$packages = [];
$pkg_res = $conn->query("SELECT id, package_name, price FROM packages ORDER BY id DESC");
if ($pkg_res) {
    while ($row = $pkg_res->fetch_assoc()) {
        $items = [];
        $item_stmt = $conn->prepare("SELECT e.name, pi.quantity FROM package_items pi JOIN equipments e ON e.id = pi.equipment_id WHERE pi.package_id = ?");
        $item_stmt->bind_param("i", $row['id']);
        $item_stmt->execute();
        $item_res = $item_stmt->get_result();
        while ($it = $item_res->fetch_assoc()) {
            $items[] = $it['quantity'] . 'x ' . $it['name'];
        }
        $item_stmt->close();
        $row['items'] = $items;
        $packages[] = $row;
    }
}

// ---------------- FETCH EQUIPMENT ----------------
$equipment = [];
$eq_res = $conn->query("SELECT e.id, e.name, e.photo, e.price, e.stock, c.category_name
                         FROM equipments e
                         JOIN categories c ON c.id = e.category_id
                         ORDER BY e.stock DESC, e.name ASC");
if ($eq_res) {
    while ($row = $eq_res->fetch_assoc()) {
        $equipment[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Rental System</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font/css/all.min.css">
    <style>
        body { padding-top: 70px; }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            border-radius: 15px;
            margin-bottom: 40px;
        }
        .feature-card {
            transition: transform 0.3s;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            border-radius: 15px 15px 0 0;
        }
        .modal-body {
            padding: 30px;
        }
        .modal-footer {
            border-radius: 0 0 15px 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46a1 100%);
        }
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section-sub {
            color: #6c757d;
            margin-bottom: 40px;
        }
        .package-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            height: 100%;
            border-top: 4px solid #667eea;
        }
        .package-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }
        .package-items li {
            padding: 4px 0;
        }
        .equipment-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            height: 100%;
            overflow: hidden;
        }
        .equipment-thumb {
            height: 110px;
            background: linear-gradient(135deg, #eef0fb, #e2e6fa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #667eea;
        }
        .equipment-category {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #764ba2;
            font-weight: 600;
        }
        .stock-badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-utensils me-2"></i>Catering Rental
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#equipment-section"><i class="fas fa-box me-1"></i>Equipment</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#packages-section"><i class="fas fa-tag me-1"></i>Packages</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about"><i class="fas fa-info-circle me-1"></i>About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact"><i class="fas fa-envelope me-1"></i>Contact</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>Account
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button></li>
                        <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus me-2"></i>Sign Up
                        </button></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Welcome to Catering Rental</h1>
            <p class="lead mb-4">Your one-stop shop for all catering equipment rentals</p>
            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="fas fa-user-plus me-2"></i>Get Started
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card feature-card h-100 text-center p-4">
                <div class="card-body">
                    <div class="feature-icon"><i class="fas fa-chair"></i></div>
                    <h5 class="card-title">Quality Equipment</h5>
                    <p class="card-text text-muted">Wide range of high-quality catering equipment for any event.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100 text-center p-4">
                <div class="card-body">
                    <div class="feature-icon"><i class="fas fa-tags"></i></div>
                    <h5 class="card-title">Affordable Packages</h5>
                    <p class="card-text text-muted">Competitive prices and special packages for every budget.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100 text-center p-4">
                <div class="card-body">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h5 class="card-title">Flexible Rental</h5>
                    <p class="card-text text-muted">Rent by day or for special events with easy returns.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- PACKAGES SHOWCASE -->
    <div id="packages-section" class="mb-5">
        <h2 class="section-title text-center">Event Packages</h2>
        <p class="section-sub text-center">All-in-one bundles that take the guesswork out of planning</p>
        <div class="row g-4">
            <?php if (empty($packages)): ?>
                <div class="col-12 text-center text-muted">No packages available yet. Check back soon!</div>
            <?php else: foreach ($packages as $pkg): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="package-card p-4">
                        <h5><i class="fas fa-clipboard-list me-2 text-primary"></i><?= htmlspecialchars($pkg['package_name']) ?></h5>
                        <div class="package-price mb-2">₱<?= number_format($pkg['price'], 2) ?></div>
                        <ul class="package-items list-unstyled mb-3">
                            <?php if (empty($pkg['items'])): ?>
                                <li class="text-muted">Contact us for details</li>
                            <?php else: foreach ($pkg['items'] as $it): ?>
                                <li><i class="fas fa-check text-success me-2"></i><?= htmlspecialchars($it) ?></li>
                            <?php endforeach; endif; ?>
                        </ul>
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#loginModal">Book This Package</button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- EQUIPMENT SHOWCASE -->
    <div id="equipment-section" class="mb-5">
        <h2 class="section-title text-center">Catering Equipment</h2>
        <p class="section-sub text-center">Everything from tableware to furniture, available for individual rental</p>
        <div class="row g-4">
            <?php if (empty($equipment)): ?>
                <div class="col-12 text-center text-muted">No equipment listed yet.</div>
            <?php else: foreach ($equipment as $eq): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="equipment-card">
                        <div class="equipment-thumb">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="p-3">
                            <span class="equipment-category"><?= htmlspecialchars($eq['category_name']) ?></span>
                            <h6 class="mt-1 mb-2"><?= htmlspecialchars($eq['name']) ?></h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>₱<?= number_format($eq['price'], 2) ?></strong>
                                <span class="badge stock-badge <?= $eq['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $eq['stock'] > 0 ? $eq['stock'] . ' left' : 'Out of stock' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ABOUT -->
    <div id="about" class="row align-items-center mb-5 g-4">
        <div class="col-lg-6">
            <h2 class="section-title">Who We Are</h2>
            <p class="text-muted">Catering Rental supplies quality tables, chairs, dinnerware, and cooking equipment for weddings, birthdays, corporate events, and everything in between. We handle delivery, setup, and pickup so you can focus on your guests.</p>
        </div>
        <div class="col-lg-6">
            <img src="https://images.unsplash.com/photo-1519671282429-b44660ead0a7?auto=format&fit=crop&w=900&q=80" class="img-fluid rounded shadow" alt="Catering setup">
        </div>
    </div>

    <!-- CONTACT -->
    <div id="contact" class="text-center mb-5">
        <h2 class="section-title">Contact Us</h2>
        <p class="text-muted mb-1"><i class="fas fa-phone me-2"></i>0917 000 0000</p>
        <p class="text-muted mb-1"><i class="fas fa-envelope me-2"></i>hello@cateringrental.com</p>
        <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Naic, Cavite, Philippines</p>
    </div>
</div>

<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Login</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    <p class="text-center text-muted small">Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Sign up here</a></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($register_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
                    <?php endif; ?>
                    <?php if ($register_success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($register_success) ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Birthday <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="birthday" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_number" placeholder="Enter contact number" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Enter address" required></textarea>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reg_username" placeholder="Choose username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="reg_password" placeholder="Min 6 characters" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                    <p class="text-center text-muted small">Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login here</a></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="register" class="btn btn-success">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/font/js/all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');

    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const target = this.getAttribute('data-bs-target');
            if (target === '#registerModal' && loginModal.classList.contains('show')) {
                bootstrap.Modal.getInstance(loginModal).hide();
            }
            if (target === '#loginModal' && registerModal.classList.contains('show')) {
                bootstrap.Modal.getInstance(registerModal).hide();
            }
        });
    });

    loginModal.addEventListener('hidden.bs.modal', function() {
        document.querySelectorAll('#loginModal .alert').forEach(el => el.remove());
    });

    registerModal.addEventListener('hidden.bs.modal', function() {
        document.querySelectorAll('#registerModal .alert').forEach(el => el.remove());
    });

    const blocked = ["'", '"', ';', '--', '<', '>', '\\', '=', '`', '|', '&', '%'];
    document.querySelectorAll('input[type=text], input[type=password], textarea').forEach(input => {
        input.addEventListener('input', function() {
            blocked.forEach(c => { this.value = this.value.split(c).join(''); });
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            let text = (e.clipboardData || window.clipboardData).getData('text');
            blocked.forEach(c => { text = text.split(c).join(''); });
            this.value += text;
        });
    });

    <?php if ($login_error): ?>
    new bootstrap.Modal(document.getElementById('loginModal')).show();
    <?php endif; ?>

    <?php if ($register_error || $register_success): ?>
    new bootstrap.Modal(document.getElementById('registerModal')).show();
    <?php endif; ?>
});
</script>
</body>
</html>