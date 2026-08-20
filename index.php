<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connection.php';
include 'classes/AdminAuth.php';
include 'classes/StaffAuth.php';
include 'classes/CustomerAuth.php';

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
$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = $_SESSION['customer_name'] ?? '';

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
                header('Location: index.php');
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
        } elseif (!in_array($gender, ['Male', 'Female'])) {
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
                $e_password = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $conn->prepare("INSERT INTO customer_info (fullname, address, gender, birthday, contact_number, username, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $fullname, $address, $gender, $birthday, $contact_number, $username, $e_password);
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

$equipment = [];
$eq_res = $conn->query("SELECT e.id, e.name, e.photo, e.price, e.stock, e.quantity, c.category_name
                         FROM equipments e
                         JOIN categories c ON c.id = e.category_id
                         ORDER BY e.stock DESC, e.name ASC");
if ($eq_res) {
    while ($row = $eq_res->fetch_assoc()) {
        $equipment[] = $row;
    }
}

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci) {
        $cartCount += (int)$ci['qty'];
    }
}

$profileFullname = '';
$profileContact = '';
$profileAddress = '';
if ($isLoggedIn) {
    $profileStmt = $conn->prepare("SELECT fullname, contact_number, address FROM customer_info WHERE id = ?");
    $profileStmt->bind_param("i", $_SESSION['customer_id']);
    $profileStmt->execute();
    $profileRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
    if ($profileRow) {
        $profileFullname = $profileRow['fullname'];
        $profileContact = $profileRow['contact_number'];
        $profileAddress = $profileRow['address'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Rental</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #F4F5F0;
            --paper-dim: #E9EBE2;
            --ink: #1C231E;
            --ink-soft: #4B564C;
            --line: #D3D8CB;
            --forest: #3F5C4C;
            --forest-dark: #2C4136;
            --stamp: #C3811F;
            --stamp-dark: #9C660F;
            --danger: #A8402A;
            --white: #FFFFFF;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--paper);
            color: var(--ink);
            margin: 0;
        }
        h1, h2, h3, h4, h5, .display-font {
            font-family: 'Archivo', sans-serif;
            letter-spacing: -0.01em;
        }
        .mono {
            font-family: 'IBM Plex Mono', monospace;
        }
        a { color: var(--forest-dark); }

        .navbar-custom {
            background: var(--paper);
            border-bottom: 1px solid var(--line);
        }
        .navbar-custom .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Archivo', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--ink);
        }
        .navbar-custom .navbar-brand img {
            height: 38px;
            width: 38px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--line);
        }
        .navbar-custom .nav-link {
            color: var(--ink-soft);
            font-weight: 500;
            font-size: 0.95rem;
        }
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            color: var(--forest-dark);
        }
        .btn-forest {
            background: var(--forest);
            border: 1px solid var(--forest);
            color: var(--white);
            font-weight: 600;
        }
        .btn-forest:hover {
            background: var(--forest-dark);
            border-color: var(--forest-dark);
            color: var(--white);
        }
        .btn-outline-forest {
            border: 1px solid var(--forest);
            color: var(--forest-dark);
            font-weight: 600;
            background: transparent;
        }
        .btn-outline-forest:hover {
            background: var(--forest);
            color: var(--white);
        }
        .btn-stamp {
            background: var(--stamp);
            border: 1px solid var(--stamp);
            color: var(--white);
            font-weight: 600;
        }
        .btn-stamp:hover {
            background: var(--stamp-dark);
            border-color: var(--stamp-dark);
            color: var(--white);
        }

        .hero {
            position: relative;
            height: 100vh;
            min-height: 560px;
            display: flex;
            align-items: flex-end;
            background-image: url('https://images.unsplash.com/photo-1519671282429-b44660ead0a7?auto=format&fit=crop&w=1800&q=80');
            background-size: cover;
            background-position: center;
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(28, 35, 30, 0.62);
        }
        .hero-inner {
            position: relative;
            z-index: 1;
            width: 100%;
            padding-bottom: 64px;
        }
        .hero-tag {
            display: inline-block;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--paper);
            border: 1px solid rgba(244,245,240,0.55);
            padding: 5px 12px;
            border-radius: 3px;
            margin-bottom: 18px;
        }
        .hero h1 {
            color: var(--white);
            font-size: clamp(2.4rem, 5.5vw, 4.4rem);
            font-weight: 800;
            line-height: 1.02;
            max-width: 780px;
        }
        .hero p {
            color: rgba(244,245,240,0.85);
            font-size: 1.15rem;
            max-width: 560px;
            margin-top: 18px;
        }
        .hero-actions {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .section { padding: 90px 0; }
        .section-tight { padding: 70px 0; }
        .section-head {
            margin-bottom: 46px;
        }
        .section-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--stamp-dark);
            margin-bottom: 8px;
            display: block;
        }
        .section-title {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 6px;
        }
        .section-sub {
            color: var(--ink-soft);
            max-width: 560px;
        }

        .tag-card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s ease, transform 0.15s ease;
        }
        .tag-card:hover {
            border-color: var(--forest);
            transform: translateY(-3px);
        }
        .tag-photo {
            aspect-ratio: 4 / 3;
            background: var(--paper-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px dashed var(--line);
        }
        .tag-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tag-photo i {
            font-size: 2rem;
            color: var(--ink-soft);
            opacity: 0.5;
        }
        .tag-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }
        .tag-code {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            color: var(--ink-soft);
        }
        .tag-category {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--forest-dark);
            font-weight: 600;
        }
        .tag-name {
            font-weight: 700;
            margin: 0;
            font-size: 1.02rem;
        }
        .tag-price {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
        }
        .gauge {
            height: 5px;
            background: var(--paper-dim);
            border-radius: 4px;
            overflow: hidden;
        }
        .gauge-fill {
            height: 100%;
            background: var(--forest);
        }
        .gauge-fill.low { background: var(--danger); }
        .gauge-fill.mid { background: var(--stamp); }
        .gauge-label {
            font-size: 0.72rem;
            color: var(--ink-soft);
            display: flex;
            justify-content: space-between;
        }
        .tag-footer {
            margin-top: auto;
            display: flex;
            gap: 8px;
        }
        .btn-add {
            flex: 1;
            font-size: 0.85rem;
            padding: 7px 10px;
        }

        .package-card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 26px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .package-card .package-number {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            color: var(--stamp-dark);
            margin-bottom: 10px;
        }
        .package-price {
            font-family: 'Archivo', sans-serif;
            font-size: 1.9rem;
            font-weight: 800;
            margin: 6px 0 16px;
        }
        .package-items {
            list-style: none;
            padding: 0;
            margin: 0 0 20px;
            border-top: 1px dashed var(--line);
            padding-top: 14px;
        }
        .package-items li {
            padding: 5px 0;
            font-size: 0.92rem;
            color: var(--ink-soft);
            display: flex;
            gap: 8px;
        }
        .package-items li i { color: var(--forest); margin-top: 3px; }

        .about-block {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }
        .about-block img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            min-height: 320px;
        }
        .about-copy { padding: 46px; }

        .contact-strip {
            background: var(--forest);
            color: var(--white);
            border-radius: 12px;
            padding: 46px;
        }
        .contact-strip a { color: var(--white); }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.98rem;
        }

        .gallery-grid {
            columns: 3 320px;
            column-gap: 20px;
        }
        .gallery-item {
            break-inside: avoid;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--white);
            cursor: pointer;
            position: relative;
        }
        .gallery-item img {
            width: 100%;
            display: block;
            transition: transform 0.35s ease;
        }
        .gallery-item:hover img {
            transform: scale(1.04);
        }
        .gallery-item::after {
            content: "\f00e";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 14px;
            right: 14px;
            width: 34px;
            height: 34px;
            background: rgba(28,35,30,0.55);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .gallery-item:hover::after { opacity: 1; }

        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(28,35,30,0.92);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }
        .lightbox.active { display: flex; }
        .lightbox img {
            max-width: 90vw;
            max-height: 88vh;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        .lightbox-close {
            position: absolute;
            top: 24px;
            right: 30px;
            color: var(--white);
            font-size: 1.8rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(244,245,240,0.15);
            border: 1px solid rgba(244,245,240,0.4);
            color: var(--white);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .lightbox-prev { left: 24px; }
        .lightbox-next { right: 24px; }

        .footer-strip {
            border-top: 1px solid var(--line);
            padding: 26px 0;
            font-size: 0.85rem;
            color: var(--ink-soft);
        }

        .modal-content { border-radius: 12px; border: 1px solid var(--line); }
        .modal-header { border-bottom: 1px solid var(--line); }
        .modal-footer { border-top: 1px solid var(--line); }

        .cart-fab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1040;
            background: var(--ink);
            color: var(--white);
            border: none;
            border-radius: 999px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(0,0,0,0.18);
        }
        .cart-fab .badge {
            background: var(--stamp);
            font-family: 'IBM Plex Mono', monospace;
        }

        .cart-line {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }
        .cart-line img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--line);
            flex-shrink: 0;
        }
        .cart-line .no-img {
            width: 56px;
            height: 56px;
            border-radius: 6px;
            border: 1px solid var(--line);
            background: var(--paper-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--ink-soft);
        }
        .qty-stepper {
            display: flex;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
        }
        .qty-stepper button {
            background: var(--paper-dim);
            border: none;
            width: 28px;
            height: 28px;
        }
        .qty-stepper input {
            width: 38px;
            border: none;
            text-align: center;
            font-family: 'IBM Plex Mono', monospace;
        }

        @media (max-width: 768px) {
            .hero { min-height: 640px; }
            .about-copy { padding: 28px; }
            .gallery-grid { columns: 2 200px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="logo.png" alt="Catering Rental logo">
            El Cielo
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if ($isLoggedIn): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($customerName ?: 'Account') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="user/dashboard.php">My bookings</a></li>
                        <li><a class="dropdown-item" href="logout.php">Log out</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <button class="btn btn-outline-forest btn-sm me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                </li>
                <li class="nav-item">
                    <button class="btn btn-forest btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">Sign up</button>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container hero-inner">
        <span class="hero-tag">El Cielo Function House, Catering & Events, Tupi, South Cotabato</span>
        <h1>Tables, chairs, and cookware, ready when your event is.</h1>
        <p>Browse the full inventory, build your order, and we'll have it delivered, set up, and picked up after</p>
        <div class="hero-actions">
            <a href="#equipment-section" class="btn btn-stamp btn-lg">Browse equipment</a>
            <a href="#packages-section" class="btn btn-outline-light btn-lg">See packages</a>
        </div>
    </div>
</section>

<div class="container">
    <div id="gallery-section" class="section-tight">
        <div class="section-head">
            <span class="section-eyebrow">Gallery</span>
            <h2 class="section-title">Events we've set up</h2>
        </div>
        <div class="gallery-grid" id="galleryGrid">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <div class="gallery-item" data-index="<?= $i - 1 ?>">
                    <img src="assets/img/<?= $i ?>.jpg" alt="Event sample <?= $i ?>" loading="lazy">
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div id="packages-section" class="section-tight">
        <div class="section-head">
            <span class="section-eyebrow">Bundles</span>
            <h2 class="section-title">Event packages</h2>
            <p class="section-sub">All-in-one bundles built from the same inventory below, priced as a set.</p>
        </div>
        <div class="row g-4">
            <?php if (empty($packages)): ?>
                <div class="col-12 text-center text-muted">No packages available yet. Check back soon.</div>
            <?php else: foreach ($packages as $i => $pkg): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="package-card">
                        <h5><?= htmlspecialchars($pkg['package_name']) ?></h5>
                        <div class="package-price">&#8369;<?= number_format($pkg['price'], 2) ?></div>
                        <ul class="package-items">
                            <?php if (empty($pkg['items'])): ?>
                                <li>Contact us for details</li>
                            <?php else: foreach ($pkg['items'] as $it): ?>
                                <li><i class="fas fa-check"></i><?= htmlspecialchars($it) ?></li>
                            <?php endforeach; endif; ?>
                        </ul>
                        <button class="btn btn-forest w-100 add-to-cart" data-type="package" data-id="<?= $pkg['id'] ?>" data-name="<?= htmlspecialchars($pkg['package_name'], ENT_QUOTES) ?>">
                            <i class="fas fa-plus me-1"></i>Add to order
                        </button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div id="equipment-section" class="section-tight">
        <div class="section-head">
            <span class="section-eyebrow">Inventory</span>
            <h2 class="section-title">Catering equipment</h2>
            <p class="section-sub">Everything from tableware to furniture, rented piece by piece.</p>
        </div>
        <div class="row g-4">
            <?php if (empty($equipment)): ?>
                <div class="col-12 text-center text-muted">No equipment listed yet.</div>
            <?php else: foreach ($equipment as $eq):
                $pct = $eq['quantity'] > 0 ? ($eq['stock'] / $eq['quantity']) * 100 : 0;
                $gaugeClass = $pct <= 30 ? 'low' : ($pct <= 60 ? 'mid' : '');
                $outOfStock = $eq['stock'] <= 0;
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="tag-card">
                        <div class="tag-photo">
                            <?php if (!empty($eq['photo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($eq['photo']) ?>" alt="<?= htmlspecialchars($eq['name'], ENT_QUOTES) ?>">
                            <?php else: ?>
                                <i class="fas fa-box-open"></i>
                            <?php endif; ?>
                        </div>
                        <div class="tag-body">
                            <span class="tag-category"><?= htmlspecialchars($eq['category_name']) ?></span>
                            <p class="tag-name"><?= htmlspecialchars($eq['name']) ?></p>
                            <div class="tag-price">&#8369;<?= number_format($eq['price'], 2) ?></div>
                            <div class="tag-footer">
                                <button class="btn btn-outline-forest btn-add add-to-cart" <?= $outOfStock ? 'disabled' : '' ?>
                                    data-type="equipment" data-id="<?= $eq['id'] ?>" data-name="<?= htmlspecialchars($eq['name'], ENT_QUOTES) ?>">
                                    <?= $outOfStock ? 'Out of stock' : '<i class="fas fa-plus me-1"></i>Add' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div id="about" class="section-tight">
        <div class="about-block row g-0 align-items-stretch">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=900&q=80" alt="Event setup">
            </div>
            <div class="col-lg-6">
                <div class="about-copy">
                    <span class="section-eyebrow">Who we are</span>
                    <h2 class="section-title">Built around one warehouse of inventory</h2>
                    <p class="section-sub">We supply tables, chairs, dinnerware, and cooking equipment for weddings, birthdays, and corporate events across South Cotabato. Everything you rent comes from the same catalog above, so what you see is what gets delivered.</p>
                </div>
            </div>
        </div>
    </div>


</div>

<div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightboxClose"><i class="fas fa-xmark"></i></button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev"><i class="fas fa-chevron-left"></i></button>
    <img src="" alt="" id="lightboxImg">
    <button class="lightbox-nav lightbox-next" id="lightboxNext"><i class="fas fa-chevron-right"></i></button>
</div>

<button class="cart-fab" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
    <i class="fas fa-bag-shopping"></i>
    Order
    <span class="badge rounded-pill" id="cartCountBadge"><?= $cartCount ?></span>
</button>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Your order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="cartLines" class="flex-grow-1 overflow-auto"></div>
        <div id="cartEmpty" class="text-center text-muted py-5" style="display:none;">
            <i class="fas fa-bag-shopping fa-2x mb-3"></i>
            <p>Nothing added yet.</p>
        </div>
        <div class="border-top pt-3 mt-3">
            <div class="d-flex justify-content-between mb-3">
                <strong>Total</strong>
                <strong class="mono" id="cartTotal">&#8369;0.00</strong>
            </div>
            <button class="btn btn-forest w-100" id="proceedCheckout">Proceed to booking details</button>
        </div>
    </div>
</div>

<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="checkoutForm">
                <div class="modal-body">
                    <div id="checkoutError" class="alert alert-danger" style="display:none;"></div>
                    <p class="text-muted small mb-3">Pre-filled from your account details. Edit any field if it needs correcting.</p>
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" class="form-control" name="customer_name" value="<?= htmlspecialchars($profileFullname) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact number</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($profileContact) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery address</label>
                        <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($profileAddress) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Borrow date</label>
                            <input type="datetime-local" class="form-control" name="borrow_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Return date</label>
                            <input type="datetime-local" class="form-control" name="return_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-forest">Submit booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="bookingSuccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="fas fa-circle-check fa-3x mb-3" style="color: var(--forest);"></i>
                <h5>Booking request sent</h5>
                <p class="text-muted mb-0">We'll reach out to confirm the schedule and total.</p>
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-forest" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="login" class="btn btn-forest">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                            <label class="form-label">Full name</label>
                            <input type="text" class="form-control" name="fullname" placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Birthday</label>
                            <input type="date" class="form-control" name="birthday" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact number</label>
                            <input type="text" class="form-control" name="contact_number" placeholder="Enter contact number" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Enter address" required></textarea>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="reg_username" placeholder="Choose username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="reg_password" placeholder="Min 6 characters" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="register" class="btn btn-forest">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/font/js/all.min.js"></script>
<script>
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', function() {
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');

    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
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
    document.querySelectorAll('#loginModal input, #registerModal input, #registerModal textarea').forEach(input => {
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
    new bootstrap.Modal(loginModal).show();
    <?php endif; ?>

    <?php if ($register_error || $register_success): ?>
    new bootstrap.Modal(registerModal).show();
    <?php endif; ?>

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isLoggedIn) {
                new bootstrap.Modal(loginModal).show();
                return;
            }
            const type = this.dataset.type;
            const id = this.dataset.id;
            addToCart(type, id, this);
        });
    });

    function addToCart(type, id, btn) {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add&type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`
        })
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = original;
            btn.disabled = false;
            if (data.success) {
                renderCart(data.cart);
            } else {
                alert(data.message || 'Unable to add item.');
            }
        })
        .catch(() => {
            btn.innerHTML = original;
            btn.disabled = false;
        });
    }

    function updateQty(type, id, qty) {
        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}&qty=${encodeURIComponent(qty)}`
        })
        .then(r => r.json())
        .then(data => { if (data.success) renderCart(data.cart); });
    }

    function removeItem(type, id) {
        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=remove&type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`
        })
        .then(r => r.json())
        .then(data => { if (data.success) renderCart(data.cart); });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function renderCart(cart) {
        const linesEl = document.getElementById('cartLines');
        const emptyEl = document.getElementById('cartEmpty');
        const totalEl = document.getElementById('cartTotal');
        const badgeEl = document.getElementById('cartCountBadge');

        let count = 0;
        let total = 0;

        if (!cart || cart.length === 0) {
            linesEl.innerHTML = '';
            emptyEl.style.display = 'block';
            totalEl.textContent = '₱0.00';
            badgeEl.textContent = '0';
            return;
        }

        emptyEl.style.display = 'none';
        linesEl.innerHTML = cart.map(item => {
            count += item.qty;
            total += item.price * item.qty;
            const thumb = item.photo
                ? `<img src="${item.type === 'equipment' ? 'uploads/' + escapeHtml(item.photo) : escapeHtml(item.photo)}" alt="">`
                : `<div class="no-img"><i class="fas fa-box"></i></div>`;
            return `
                <div class="cart-line">
                    ${thumb}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">${escapeHtml(item.name)}</span>
                            <button class="btn btn-sm btn-link text-danger p-0 remove-line" data-type="${item.type}" data-id="${item.id}"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="text-muted small mono">₱${item.price.toFixed(2)} each</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="qty-stepper">
                                <button type="button" class="qty-minus" data-type="${item.type}" data-id="${item.id}">-</button>
                                <input type="text" value="${item.qty}" readonly>
                                <button type="button" class="qty-plus" data-type="${item.type}" data-id="${item.id}">+</button>
                            </div>
                            <span class="mono">₱${(item.price * item.qty).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        totalEl.textContent = '₱' + total.toFixed(2);
        badgeEl.textContent = count;

        document.querySelectorAll('.qty-plus').forEach(b => {
            b.addEventListener('click', function() {
                const item = cart.find(i => i.type === this.dataset.type && String(i.id) === this.dataset.id);
                updateQty(this.dataset.type, this.dataset.id, item.qty + 1);
            });
        });
        document.querySelectorAll('.qty-minus').forEach(b => {
            b.addEventListener('click', function() {
                const item = cart.find(i => i.type === this.dataset.type && String(i.id) === this.dataset.id);
                const newQty = item.qty - 1;
                if (newQty <= 0) {
                    removeItem(this.dataset.type, this.dataset.id);
                } else {
                    updateQty(this.dataset.type, this.dataset.id, newQty);
                }
            });
        });
        document.querySelectorAll('.remove-line').forEach(b => {
            b.addEventListener('click', function() {
                removeItem(this.dataset.type, this.dataset.id);
            });
        });
    }

    function loadCart() {
        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get'
        })
        .then(r => r.json())
        .then(data => { if (data.success) renderCart(data.cart); });
    }

    if (isLoggedIn) loadCart();

    document.getElementById('proceedCheckout').addEventListener('click', function() {
        const badge = document.getElementById('cartCountBadge');
        if (parseInt(badge.textContent || '0', 10) === 0) {
            alert('Add at least one item first.');
            return;
        }
        bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas'))?.hide();
        new bootstrap.Modal(document.getElementById('checkoutModal')).show();
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const errorBox = document.getElementById('checkoutError');
        errorBox.style.display = 'none';
        const formData = new URLSearchParams(new FormData(this));
        formData.append('action', 'checkout');

        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                new bootstrap.Modal(document.getElementById('bookingSuccessModal')).show();
                renderCart([]);
                setTimeout(() => window.location.reload(), 1400);
            } else {
                errorBox.textContent = data.message || 'Something went wrong.';
                errorBox.style.display = 'block';
            }
        })
        .catch(() => {
            errorBox.textContent = 'Something went wrong. Please try again.';
            errorBox.style.display = 'block';
        });
    });

    const galleryItems = Array.from(document.querySelectorAll('.gallery-item'));
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    let currentGalleryIndex = 0;

    function openLightbox(index) {
        currentGalleryIndex = index;
        lightboxImg.src = galleryItems[currentGalleryIndex].querySelector('img').src;
        lightbox.classList.add('active');
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
    }

    function nextImage() {
        currentGalleryIndex = (currentGalleryIndex + 1) % galleryItems.length;
        lightboxImg.src = galleryItems[currentGalleryIndex].querySelector('img').src;
    }

    function prevImage() {
        currentGalleryIndex = (currentGalleryIndex - 1 + galleryItems.length) % galleryItems.length;
        lightboxImg.src = galleryItems[currentGalleryIndex].querySelector('img').src;
    }

    galleryItems.forEach((item, index) => {
        item.addEventListener('click', () => openLightbox(index));
    });

    document.getElementById('lightboxClose').addEventListener('click', closeLightbox);
    document.getElementById('lightboxNext').addEventListener('click', nextImage);
    document.getElementById('lightboxPrev').addEventListener('click', prevImage);

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    });
});
</script>
</body>
</html>