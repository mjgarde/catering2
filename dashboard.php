<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connection.php';
include 'classes/AdminAuth.php';
include 'classes/StaffAuth.php';
include 'classes/CustomerAuth.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'] ?? '';

define('PENALTY_PER_HOUR', 100.00);

function computeOverdueHours($returnDate, $compareDate) {
    if ($compareDate <= $returnDate) {
        return 0;
    }
    $diffSeconds = $compareDate->getTimestamp() - $returnDate->getTimestamp();
    return (int) ceil($diffSeconds / 3600);
}

$bookings = [];
$stmt = $conn->prepare("SELECT id, customer_name, email, phone, address, borrow_date, return_date,
                                total_amount, status, created_at, actual_return_date,
                                fine_amount, damage_fee, damage_notes, damaged_items
                         FROM customer_booking
                         WHERE customer_id = ?
                         ORDER BY return_date ASC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$now = new DateTime();

while ($row = $result->fetch_assoc()) {
    $returnDate = new DateTime($row['return_date']);
    $borrowDate = new DateTime($row['borrow_date']);

    $row['items'] = [];
    $line_stmt = $conn->prepare("SELECT bi.id, bi.equipment_id, bi.package_id, bi.quantity, bi.price,
                                         e.name AS equipment_name, e.photo AS equipment_photo,
                                         p.package_name
                                  FROM booking_items bi
                                  LEFT JOIN equipments e ON e.id = bi.equipment_id
                                  LEFT JOIN packages p ON p.id = bi.package_id
                                  WHERE bi.booking_id = ?");
    $line_stmt->bind_param("i", $row['id']);
    $line_stmt->execute();
    $line_res = $line_stmt->get_result();

    while ($line = $line_res->fetch_assoc()) {
        if ($line['package_id']) {
            $subItems = [];
            $sub_stmt = $conn->prepare("SELECT e.name, e.photo, pit.quantity
                                         FROM package_items pit
                                         JOIN equipments e ON e.id = pit.equipment_id
                                         WHERE pit.package_id = ?");
            $sub_stmt->bind_param("i", $line['package_id']);
            $sub_stmt->execute();
            $sub_res = $sub_stmt->get_result();
            while ($sub = $sub_res->fetch_assoc()) {
                $subItems[] = $sub;
            }
            $sub_stmt->close();

            $row['items'][] = [
                'type' => 'package',
                'name' => $line['package_name'],
                'quantity' => $line['quantity'],
                'price' => $line['price'],
                'sub_items' => $subItems
            ];
        } else {
            $row['items'][] = [
                'type' => 'equipment',
                'name' => $line['equipment_name'],
                'photo' => $line['equipment_photo'],
                'quantity' => $line['quantity'],
                'price' => $line['price']
            ];
        }
    }
    $line_stmt->close();

    if ($row['status'] === 'Returned' && !empty($row['actual_return_date'])) {
        $actualReturn = new DateTime($row['actual_return_date']);
        $overdueHours = computeOverdueHours($returnDate, $actualReturn);
        $computedPenalty = $overdueHours * PENALTY_PER_HOUR;
        $row['penalty'] = ($row['fine_amount'] > 0) ? (float) $row['fine_amount'] : $computedPenalty;
        $row['overdue_hours'] = $overdueHours;
        $row['is_overdue'] = $overdueHours > 0;
    } elseif ($row['status'] === 'Borrowed') {
        $overdueHours = computeOverdueHours($returnDate, $now);
        $row['penalty'] = $overdueHours * PENALTY_PER_HOUR;
        $row['overdue_hours'] = $overdueHours;
        $row['is_overdue'] = $overdueHours > 0;
    } else {
        $row['penalty'] = 0;
        $row['overdue_hours'] = 0;
        $row['is_overdue'] = false;
    }

    $row['return_timestamp'] = $returnDate->getTimestamp();
    $row['borrow_timestamp'] = $borrowDate->getTimestamp();

    $bookings[] = $row;
}
$stmt->close();

$grouped = ['Pending' => [], 'Borrowed' => [], 'Returned' => []];
foreach ($bookings as $b) {
    $grouped[$b['status']][] = $b;
}

$tabs = [
    ['key' => 'Pending', 'label' => 'Pending', 'icon' => 'fa-clock'],
    ['key' => 'Borrowed', 'label' => 'Borrowed', 'icon' => 'fa-truck-fast'],
    ['key' => 'Returned', 'label' => 'Completed', 'icon' => 'fa-circle-check'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings — Catering Rental</title>
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
            --danger-dark: #7C2E1D;
            --white: #FFFFFF;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--paper);
            color: var(--ink);
            margin: 0;
        }
        h1, h2, h3, h4, h5, .display-font { font-family: 'Archivo', sans-serif; letter-spacing: -0.01em; }
        .mono { font-family: 'IBM Plex Mono', monospace; }

        .navbar-custom { background: var(--paper); border-bottom: 1px solid var(--line); }
        .navbar-custom .navbar-brand {
            display: flex; align-items: center; gap: 10px;
            font-family: 'Archivo', sans-serif; font-weight: 700; font-size: 1.15rem; color: var(--ink);
        }
        .navbar-custom .navbar-brand img { height: 38px; width: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line); }
        .btn-forest { background: var(--forest); border: 1px solid var(--forest); color: var(--white); font-weight: 600; }
        .btn-forest:hover { background: var(--forest-dark); border-color: var(--forest-dark); color: var(--white); }
        .btn-outline-forest { border: 1px solid var(--forest); color: var(--forest-dark); font-weight: 600; background: transparent; }
        .btn-outline-forest:hover { background: var(--forest); color: var(--white); }

        .page-head { padding: 46px 0 26px; }
        .section-eyebrow {
            font-family: 'IBM Plex Mono', monospace; font-size: 0.78rem; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--stamp-dark); margin-bottom: 8px; display: block;
        }
        .section-title { font-weight: 800; font-size: 1.9rem; margin-bottom: 6px; }

        .stat-strip { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 30px; }
        .stat-card {
            flex: 1; min-width: 150px;
            background: var(--white); border: 1px solid var(--line); border-radius: 10px;
            padding: 16px 18px;
        }
        .stat-card .stat-value { font-family: 'Archivo', sans-serif; font-weight: 800; font-size: 1.7rem; }
        .stat-card .stat-label { font-size: 0.78rem; color: var(--ink-soft); text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-card.alert-stat .stat-value { color: var(--danger-dark); }

        .tab-nav {
            display: flex; gap: 4px; border-bottom: 1px solid var(--line);
            margin-bottom: 26px; overflow-x: auto;
        }
        .tab-btn {
            border: none; background: none; padding: 12px 20px;
            font-weight: 600; font-size: 0.92rem; color: var(--ink-soft);
            border-bottom: 2px solid transparent; white-space: nowrap;
            display: flex; align-items: center; gap: 8px;
        }
        .tab-btn.active { color: var(--forest-dark); border-bottom-color: var(--forest); }
        .tab-count {
            background: var(--paper-dim); color: var(--ink-soft);
            font-size: 0.7rem; font-weight: 700; padding: 1px 8px; border-radius: 20px;
        }
        .tab-btn.active .tab-count { background: var(--forest); color: var(--white); }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .booking-card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 22px;
        }
        .booking-card.is-overdue { border-color: var(--danger); }

        .booking-top {
            display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;
            margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--line);
        }
        .booking-placed { font-size: 0.8rem; color: var(--ink-soft); }
        .status-badge {
            display: inline-block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.04em; padding: 4px 10px; border-radius: 20px;
        }
        .status-Pending { background: #F1E6D0; color: var(--stamp-dark); }
        .status-Borrowed { background: #DCE7DF; color: var(--forest-dark); }
        .status-Returned { background: #E4E6E0; color: var(--ink-soft); }

        .booking-dates { display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 16px; }
        .date-block .label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-soft); margin-bottom: 3px; }
        .date-block .value { font-weight: 600; font-size: 0.95rem; }

        .countdown-box {
            background: var(--paper-dim);
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 16px;
        }
        .countdown-box.overdue { background: #F6E2DC; }
        .countdown-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-soft); margin-bottom: 6px; }
        .countdown-timer { font-family: 'IBM Plex Mono', monospace; font-size: 1.35rem; font-weight: 600; color: var(--forest-dark); }
        .countdown-timer.overdue { color: var(--danger-dark); }
        .penalty-note { margin-top: 8px; font-size: 0.9rem; color: var(--danger-dark); font-weight: 600; }
        .penalty-note.final { color: var(--ink-soft); }

        .booking-items { list-style: none; padding: 0; margin: 0 0 16px; display: flex; flex-direction: column; gap: 10px; }
        .item-row {
            display: flex; align-items: center; gap: 12px;
            padding: 8px; border: 1px solid var(--line); border-radius: 8px;
        }
        .item-thumb {
            width: 48px; height: 48px; border-radius: 6px; object-fit: cover;
            border: 1px solid var(--line); background: var(--paper-dim); flex-shrink: 0;
        }
        .item-thumb-placeholder {
            width: 48px; height: 48px; border-radius: 6px; border: 1px solid var(--line);
            background: var(--paper-dim); display: flex; align-items: center; justify-content: center;
            color: var(--ink-soft); flex-shrink: 0;
        }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; font-size: 0.92rem; }
        .item-qty { font-size: 0.78rem; color: var(--ink-soft); }
        .item-price { font-family: 'IBM Plex Mono', monospace; font-size: 0.88rem; }
        .package-badge {
            font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: var(--stamp-dark); font-weight: 700; margin-left: 6px;
        }
        .package-sub-items {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 8px 4px 68px;
        }
        .package-sub-item {
            display: flex; align-items: center; gap: 6px;
            border: 1px dashed var(--line); border-radius: 20px; padding: 3px 10px 3px 3px;
        }
        .package-sub-thumb {
            width: 26px; height: 26px; border-radius: 50%; object-fit: cover;
            border: 1px solid var(--line); background: var(--paper-dim);
        }
        .package-sub-thumb-placeholder {
            width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--line);
            background: var(--paper-dim); display: flex; align-items: center; justify-content: center;
            color: var(--ink-soft); font-size: 0.6rem;
        }
        .package-sub-label { font-size: 0.75rem; color: var(--ink-soft); }

        .booking-total {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 14px; border-top: 1px dashed var(--line); font-weight: 700; font-size: 1.05rem;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--ink-soft); }
        .empty-state i { font-size: 2.2rem; margin-bottom: 14px; opacity: 0.5; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="logo.png" alt="Catering Rental logo">
            El Cielo
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-sm-inline"><?= htmlspecialchars($customerName) ?></span>
            <a href="index.php" class="btn btn-outline-forest btn-sm">Back to shop</a>
            <a href="logout.php" class="btn btn-forest btn-sm">Log out</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-head">
        <span class="section-eyebrow">Your account</span>
        <h1 class="section-title">My bookings</h1>
        <p class="text-muted mb-0">Track every rental from request to return. Every hour past the return date adds ₱100.00 in penalty.</p>
    </div>

    <div class="stat-strip">
        <div class="stat-card">
            <div class="stat-value"><?= count($grouped['Pending']) ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($grouped['Borrowed']) ?></div>
            <div class="stat-label">Borrowed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($grouped['Returned']) ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <?php
            $overdueCount = 0;
            foreach ($bookings as $b) {
                if ($b['is_overdue'] && $b['status'] !== 'Returned') $overdueCount++;
            }
        ?>
        <div class="stat-card alert-stat">
            <div class="stat-value"><?= $overdueCount ?></div>
            <div class="stat-label">Overdue now</div>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-xmark"></i>
            <p>You don't have any bookings yet.</p>
            <a href="index.php#equipment-section" class="btn btn-forest">Browse equipment</a>
        </div>
    <?php else: ?>
        <div class="tab-nav">
            <?php foreach ($tabs as $i => $tab): ?>
            <button class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="<?= $tab['key'] ?>">
                <i class="fas <?= $tab['icon'] ?>"></i>
                <?= $tab['label'] ?>
                <span class="tab-count"><?= count($grouped[$tab['key']]) ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($tabs as $i => $tab): ?>
        <div class="tab-panel <?= $i === 0 ? 'active' : '' ?>" id="panel-<?= $tab['key'] ?>">
            <?php if (empty($grouped[$tab['key']])): ?>
                <div class="empty-state">
                    <i class="fas <?= $tab['icon'] ?>"></i>
                    <p>No <?= strtolower($tab['label']) ?> bookings right now.</p>
                </div>
            <?php else: foreach ($grouped[$tab['key']] as $b): ?>
                <div class="booking-card <?= $b['is_overdue'] ? 'is-overdue' : '' ?>">
                    <div class="booking-top">
                        <div class="booking-placed">Placed <?= date('M d, Y g:i A', strtotime($b['created_at'])) ?></div>
                        <span class="status-badge status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span>
                    </div>

                    <div class="booking-dates">
                        <div class="date-block">
                            <div class="label">Borrow date</div>
                            <div class="value"><?= date('M d, Y g:i A', $b['borrow_timestamp']) ?></div>
                        </div>
                        <div class="date-block">
                            <div class="label">Return date</div>
                            <div class="value"><?= date('M d, Y g:i A', $b['return_timestamp']) ?></div>
                        </div>
                        <?php if ($b['status'] === 'Returned' && !empty($b['actual_return_date'])): ?>
                        <div class="date-block">
                            <div class="label">Actually returned</div>
                            <div class="value"><?= date('M d, Y g:i A', strtotime($b['actual_return_date'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($b['status'] === 'Returned'): ?>
                        <div class="countdown-box <?= $b['is_overdue'] ? 'overdue' : '' ?>">
                            <div class="countdown-label">Return outcome</div>
                            <?php if ($b['is_overdue']): ?>
                                <div class="countdown-timer overdue">
                                    Returned <?= $b['overdue_hours'] ?> hour<?= $b['overdue_hours'] > 1 ? 's' : '' ?> late
                                </div>
                                <div class="penalty-note final">
                                    Penalty charged: ₱<?= number_format($b['penalty'], 2) ?>
                                    (<?= $b['overdue_hours'] ?> hr × ₱100.00)
                                </div>
                            <?php else: ?>
                                <div class="countdown-timer">Returned on time</div>
                                <div class="penalty-note final">No penalty applied.</div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($b['status'] === 'Borrowed'): ?>
                        <div class="countdown-box <?= $b['is_overdue'] ? 'overdue' : '' ?>" data-return-ts="<?= $b['return_timestamp'] ?>">
                            <div class="countdown-label"><?= $b['is_overdue'] ? 'Overdue by' : 'Time remaining' ?></div>
                            <div class="countdown-timer <?= $b['is_overdue'] ? 'overdue' : '' ?> js-countdown">calculating...</div>
                            <div class="penalty-note js-penalty" style="<?= $b['is_overdue'] ? '' : 'display:none;' ?>"></div>
                        </div>
                    <?php else: ?>
                        <div class="countdown-box">
                            <div class="countdown-label">Status</div>
                            <div class="countdown-timer">Awaiting confirmation</div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($b['items'])): ?>
                    <ul class="booking-items">
                        <?php foreach ($b['items'] as $it): ?>
                            <li>
                                <div class="item-row">
                                    <?php if ($it['type'] === 'equipment'): ?>
                                        <?php if (!empty($it['photo'])): ?>
                                            <img class="item-thumb" src="uploads/<?= htmlspecialchars($it['photo']) ?>" alt="<?= htmlspecialchars($it['name'] ?? 'Item', ENT_QUOTES) ?>">
                                        <?php else: ?>
                                            <div class="item-thumb-placeholder"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="item-thumb-placeholder"><i class="fas fa-gifts"></i></div>
                                    <?php endif; ?>
                                    <div class="item-info">
                                        <span class="item-name">
                                            <?= htmlspecialchars($it['name'] ?? 'Item') ?>
                                            <?php if ($it['type'] === 'package'): ?><span class="package-badge">Package</span><?php endif; ?>
                                        </span>
                                        <div class="item-qty"><?= $it['quantity'] ?> pc<?= $it['quantity'] > 1 ? 's' : '' ?></div>
                                    </div>
                                    <div class="item-price mono">₱<?= number_format($it['price'] * $it['quantity'], 2) ?></div>
                                </div>
                                <?php if ($it['type'] === 'package' && !empty($it['sub_items'])): ?>
                                <div class="package-sub-items">
                                    <?php foreach ($it['sub_items'] as $sub): ?>
                                    <div class="package-sub-item">
                                        <?php if (!empty($sub['photo'])): ?>
                                            <img class="package-sub-thumb" src="uploads/<?= htmlspecialchars($sub['photo']) ?>" alt="<?= htmlspecialchars($sub['name'], ENT_QUOTES) ?>">
                                        <?php else: ?>
                                            <div class="package-sub-thumb-placeholder"><i class="fas fa-box"></i></div>
                                        <?php endif; ?>
                                        <span class="package-sub-label"><?= $sub['quantity'] ?>x <?= htmlspecialchars($sub['name']) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (!empty($b['damage_notes']) || (float)$b['damage_fee'] > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <strong>Damage fee:</strong> ₱<?= number_format($b['damage_fee'], 2) ?>
                        <?php if (!empty($b['damage_notes'])): ?> — <?= htmlspecialchars($b['damage_notes']) ?><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="booking-total">
                        <span>Order total</span>
                        <span class="mono">₱<?= number_format($b['total_amount'] + $b['penalty'] + (float)$b['damage_fee'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
const PENALTY_RATE = 100.00;

function formatDuration(totalSeconds) {
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);
    const parts = [];
    if (days > 0) parts.push(days + 'd');
    parts.push(String(hours).padStart(2, '0') + 'h');
    parts.push(String(minutes).padStart(2, '0') + 'm');
    parts.push(String(seconds).padStart(2, '0') + 's');
    return parts.join(' ');
}

function tickCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.countdown-box[data-return-ts]').forEach(box => {
        const returnTs = parseInt(box.dataset.returnTs, 10);
        const timerEl = box.querySelector('.js-countdown');
        const penaltyEl = box.querySelector('.js-penalty');
        const labelEl = box.querySelector('.countdown-label');
        const diff = returnTs - now;

        if (diff > 0) {
            box.classList.remove('overdue');
            timerEl.classList.remove('overdue');
            labelEl.textContent = 'Time remaining';
            timerEl.textContent = formatDuration(diff);
            penaltyEl.style.display = 'none';
        } else {
            const overdueSeconds = -diff;
            const overdueHours = Math.ceil(overdueSeconds / 3600);
            const penalty = overdueHours * PENALTY_RATE;

            box.classList.add('overdue');
            timerEl.classList.add('overdue');
            labelEl.textContent = 'Overdue by';
            timerEl.textContent = formatDuration(overdueSeconds);
            penaltyEl.style.display = 'block';
            penaltyEl.textContent = `Running penalty: ₱${penalty.toFixed(2)} (${overdueHours} hr × ₱100.00)`;
        }
    });
}

tickCountdowns();
setInterval(tickCountdowns, 1000);

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const key = this.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('panel-' + key).classList.add('active');
    });
});
</script>

</body>
</html>