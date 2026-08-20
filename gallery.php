<?php
session_start();
include 'includes/db_connection.php';

$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = $_SESSION['customer_name'] ?? '';

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci) {
        $cartCount += (int)$ci['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Work - Catering Rental</title>
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
        .mono { font-family: 'IBM Plex Mono', monospace; }
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

        .page-hero {
            padding: 150px 0 60px;
            text-align: center;
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
            font-size: 2.4rem;
            margin-bottom: 10px;
        }
        .section-sub {
            color: var(--ink-soft);
            max-width: 620px;
            margin: 0 auto;
        }

        .gallery-section { padding: 40px 0 100px; }
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
            text-align: center;
        }

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
            text-decoration: none;
        }
        .cart-fab .badge {
            background: var(--stamp);
            font-family: 'IBM Plex Mono', monospace;
        }

        @media (max-width: 768px) {
            .gallery-grid { columns: 2 200px; }
            .page-hero { padding: 130px 0 40px; }
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
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="gallery.php">Our Work</a>
                </li>
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
                    <a class="btn btn-outline-forest btn-sm me-2" href="index.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-forest btn-sm" href="index.php">Sign up</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="page-hero container">
    <span class="section-eyebrow">Gallery</span>
    <h1 class="section-title">Events we've set up</h1>
    <p class="section-sub">A look at past bookings, from full table settings to complete event layouts, all built from our rental inventory.</p>
</div>

<div class="container gallery-section">
    <div class="gallery-grid" id="galleryGrid">
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <div class="gallery-item" data-index="<?= $i - 1 ?>">
                <img src="assets/img/<?= $i ?>.jpg" alt="Event sample <?= $i ?>" loading="lazy">
            </div>
        <?php endfor; ?>
    </div>
</div>

<div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightboxClose"><i class="fas fa-xmark"></i></button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev"><i class="fas fa-chevron-left"></i></button>
    <img src="" alt="" id="lightboxImg">
    <button class="lightbox-nav lightbox-next" id="lightboxNext"><i class="fas fa-chevron-right"></i></button>
</div>

<a href="index.php#equipment-section" class="cart-fab">
    <i class="fas fa-bag-shopping"></i>
    Order
    <span class="badge rounded-pill"><?= $cartCount ?></span>
</a>

<div class="footer-strip">
    <div class="container">&copy; <?= date('Y') ?> El Cielo Function House, Catering & Events, Tupi, South Cotabato</div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/font/js/all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const items = Array.from(document.querySelectorAll('.gallery-item'));
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    let current = 0;

    function open(index) {
        current = index;
        lightboxImg.src = items[current].querySelector('img').src;
        lightbox.classList.add('active');
    }

    function close() {
        lightbox.classList.remove('active');
    }

    function next() {
        current = (current + 1) % items.length;
        lightboxImg.src = items[current].querySelector('img').src;
    }

    function prev() {
        current = (current - 1 + items.length) % items.length;
        lightboxImg.src = items[current].querySelector('img').src;
    }

    items.forEach((item, index) => {
        item.addEventListener('click', () => open(index));
    });

    document.getElementById('lightboxClose').addEventListener('click', close);
    document.getElementById('lightboxNext').addEventListener('click', next);
    document.getElementById('lightboxPrev').addEventListener('click', prev);

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) close();
    });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
    });
});
</script>
</body>
</html>