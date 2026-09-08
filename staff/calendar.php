<?php
session_start();
include '../includes/db_connection.php';
include '../classes/StaffAuth.php';

define('ENC_KEY', 'YourSecretKey1234567890abcdef12');
define('ENC_METHOD', 'AES-256-CBC');

function dec($data) {
    if ($data === null || $data === '') return '';
    $decoded = base64_decode($data);
    if (strlen($decoded) < 16) return $data;
    $iv     = substr($decoded, 0, 16);
    $result = openssl_decrypt(substr($decoded, 16), ENC_METHOD, ENC_KEY, 0, $iv);
    return $result !== false ? $result : $data;
}

$staffAuth = new StaffAuth($conn);
if (!$staffAuth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$staff_firstname = $_SESSION['staff_firstname'] ?? 'Staff';

$bookings = [];
$res = $conn->query("
    SELECT cb.id, cb.customer_name, cb.phone, cb.address, cb.borrow_date, cb.return_date, cb.status, cb.total_amount
    FROM customer_booking cb
    WHERE cb.status IN ('Borrowed', 'Returned')
    ORDER BY cb.borrow_date ASC
");
while ($row = $res->fetch_assoc()) {
    $row['items'] = [];
    $line_stmt = $conn->prepare("SELECT bi.equipment_id, bi.package_id, bi.quantity, bi.price,
                                         e.name AS equipment_name, p.package_name
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
            $sub_stmt = $conn->prepare("SELECT e.name, pit.quantity
                                         FROM package_items pit
                                         JOIN equipments e ON e.id = pit.equipment_id
                                         WHERE pit.package_id = ?");
            $sub_stmt->bind_param("i", $line['package_id']);
            $sub_stmt->execute();
            $sub_res = $sub_stmt->get_result();
            while ($sub = $sub_res->fetch_assoc()) {
                $subItems[] = $sub['quantity'] . 'x ' . $sub['name'];
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
                'quantity' => $line['quantity'],
                'price' => $line['price']
            ];
        }
    }
    $line_stmt->close();

    $bookings[] = $row;
}

$events = [];
foreach ($bookings as $b) {
    $isOverdue = strtotime($b['return_date']) < time();

    $eventTitle = $b['status'] === 'Returned' ? 'Returned' : 'Book';

    $events[] = [
        'id' => $b['id'] . '-borrow',
        'title' => $eventTitle,
        'start' => date('c', strtotime($b['borrow_date'])),
        'extendedProps' => [
            'booking_id' => $b['id'],
            'kind' => 'Pickup',
            'status' => $b['status'],
            'customer' => dec($b['customer_name']),
            'phone' => dec($b['phone'] ?? ''),
            'address' => dec($b['address'] ?? ''),
            'time' => date('g:i A', strtotime($b['borrow_date'])),
            'date' => date('M d, Y', strtotime($b['borrow_date'])),
            'return_date' => date('M d, Y g:i A', strtotime($b['return_date'])),
            'total_amount' => (float)$b['total_amount'],
            'items' => $b['items'],
            'overdue' => $isOverdue,
        ]
    ];
}

$activeCount = 0;
$overdueCount = 0;
$returnedCount = 0;
foreach ($bookings as $b) {
    if ($b['status'] === 'Returned') {
        $returnedCount++;
    } elseif (strtotime($b['return_date']) < time()) {
        $overdueCount++;
    } else {
        $activeCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Calendar</title>
<link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/font/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.css">
<style>
    :root {
        --cal-ink: #1F2328;
        --cal-ink-soft: #57606A;
        --cal-ink-faint: #8B949E;
        --cal-border: #E1E4E8;
        --cal-border-soft: #EDEFF2;
        --cal-surface: #FFFFFF;
        --cal-surface-muted: #F6F8FA;
        --cal-accent: #1F6FEB;
        --cal-accent-soft: #EEF3FC;
        --cal-accent-soft-hover: #DEEAFB;
        --cal-danger: #CF222E;
        --cal-danger-soft: #FDECEA;
        --cal-danger-soft-hover: #FADBD8;
        --cal-neutral: #6E7781;
        --cal-neutral-soft: #F1F3F5;
        --cal-neutral-soft-hover: #E7E9EC;
        --cal-radius: 8px;
    }

    body { background: #FAFBFC; }

    a, a:hover, a:focus { text-decoration: none !important; }
    .fc a { text-decoration: none !important; }

    /* ---------- Page header ---------- */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 22px;
    }
    .page-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--cal-ink);
        margin: 0 0 4px;
        letter-spacing: -0.01em;
    }
    .page-header p {
        color: var(--cal-ink-soft);
        font-size: 0.92rem;
        margin: 0;
    }
    .header-stats {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--cal-surface);
        border: 1px solid var(--cal-border);
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--cal-ink-soft);
    }
    .stat-chip .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .stat-chip .dot.active { background: var(--cal-accent); }
    .stat-chip .dot.overdue { background: var(--cal-danger); }
    .stat-chip .dot.returned { background: var(--cal-neutral); }
    .stat-chip strong { color: var(--cal-ink); font-weight: 700; }

    /* ---------- Calendar surface ---------- */
    .calendar-card {
        background: var(--cal-surface);
        border: 1px solid var(--cal-border);
        border-radius: 10px;
        overflow: hidden;
    }
    .calendar-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--cal-border-soft);
        background: var(--cal-surface);
    }
    .calendar-legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--cal-ink-soft);
    }
    .legend-swatch {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        flex-shrink: 0;
        border-left: 3px solid transparent;
    }
    .legend-swatch.legend-book { background: var(--cal-accent-soft); border-left-color: var(--cal-accent); }
    .legend-swatch.legend-overdue { background: var(--cal-danger-soft); border-left-color: var(--cal-danger); }
    .legend-swatch.legend-returned { background: var(--cal-neutral-soft); border-left-color: var(--cal-neutral); }

    .calendar-card-body {
        padding: 18px;
    }
    #calendar { max-width: 100%; }

    /* ---------- FullCalendar base ---------- */
    .fc { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; }

    .fc .fc-toolbar {
        padding: 0 0 16px;
        gap: 10px;
    }
    .fc .fc-toolbar-title {
        font-size: 1.05rem !important;
        font-weight: 700 !important;
        color: var(--cal-ink);
    }
    .fc .fc-button {
        background: transparent !important;
        border: 1px solid var(--cal-border) !important;
        color: var(--cal-ink-soft) !important;
        font-weight: 500 !important;
        font-size: 0.82rem !important;
        box-shadow: none !important;
        text-transform: capitalize !important;
        border-radius: 6px !important;
        padding: 6px 13px !important;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .fc .fc-button:hover {
        background: var(--cal-surface-muted) !important;
        color: var(--cal-ink) !important;
        border-color: var(--cal-border) !important;
    }
    .fc .fc-button:focus,
    .fc .fc-button:focus-visible {
        outline: 2px solid rgba(31, 111, 235, 0.35) !important;
        outline-offset: 1px;
    }
    .fc .fc-button-active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--cal-ink) !important;
        border-color: var(--cal-ink) !important;
        color: #fff !important;
    }
    .fc .fc-button-group { gap: 2px; }
    .fc .fc-today-button {
        text-transform: capitalize !important;
    }

    .fc-theme-standard td, .fc-theme-standard th {
        border: 1px solid var(--cal-border-soft) !important;
    }
    .fc-theme-standard .fc-scrollgrid {
        border: none !important;
        border-top: 1px solid var(--cal-border-soft) !important;
        border-radius: 6px;
        overflow: hidden;
    }

    .fc .fc-col-header-cell {
        background: var(--cal-surface-muted);
        padding: 10px 0 !important;
        border-bottom: 1px solid var(--cal-border) !important;
    }
    .fc .fc-col-header-cell-cushion {
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cal-neutral);
        pointer-events: none;
    }
    .fc-day-sun .fc-col-header-cell-cushion { color: var(--cal-danger) !important; }

    .fc-daygrid-day-frame {
        min-height: 100px;
        padding: 7px;
        transition: background-color 0.12s ease;
    }
    .fc-daygrid-day:hover .fc-daygrid-day-frame {
        background: rgba(31, 111, 235, 0.03);
    }
    .fc .fc-daygrid-day-top {
        justify-content: flex-start;
    }
    .fc .fc-daygrid-day-number {
        text-decoration: none !important;
        font-weight: 500;
        font-size: 0.84rem;
        color: var(--cal-ink);
        padding: 2px 7px;
        border-radius: 6px;
        transition: background-color 0.12s ease;
    }
    .fc-day-sun .fc-daygrid-day-number { color: var(--cal-danger); }
    .fc-day-other .fc-daygrid-day-number { color: #C4C9D0; }
    .fc-day-sat .fc-daygrid-day-frame,
    .fc-day-sun .fc-daygrid-day-frame {
        background: rgba(110, 119, 129, 0.025);
    }

    .fc .fc-day-today {
        background: #F5F9FF !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        background: var(--cal-accent);
        color: #ffffff !important;
        font-weight: 700;
    }

    .fc-daygrid-event-harness { margin-top: 2px !important; }

    .fc-event {
        cursor: pointer;
        font-size: 0.72rem;
        background: var(--cal-accent-soft) !important;
        color: var(--cal-ink) !important;
        border: none !important;
        border-left: 3px solid var(--cal-accent) !important;
        border-radius: 4px !important;
        padding: 3px 7px !important;
        margin-bottom: 2px !important;
        text-decoration: none !important;
        font-weight: 500;
        transition: transform 0.1s ease, background-color 0.12s ease;
    }
    .fc-event:hover {
        background: var(--cal-accent-soft-hover) !important;
        transform: translateY(-1px);
    }
    .fc-event.overdue-event {
        background: var(--cal-danger-soft) !important;
        border-left-color: var(--cal-danger) !important;
        color: var(--cal-danger) !important;
        font-weight: 600;
    }
    .fc-event.overdue-event:hover {
        background: var(--cal-danger-soft-hover) !important;
    }
    .fc-event.returned-event {
        background: var(--cal-neutral-soft) !important;
        border-left-color: var(--cal-neutral) !important;
        color: var(--cal-neutral) !important;
    }
    .fc-event.returned-event:hover {
        background: var(--cal-neutral-soft-hover) !important;
    }
    .fc-event .fc-event-title {
        text-decoration: none !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fc-daygrid-event-dot { display: none; }

    .fc-list-event:hover td { background: var(--cal-surface-muted) !important; }
    .fc-list-day-cushion { background: var(--cal-surface-muted) !important; }

    /* ---------- Event details modal ---------- */
    #eventModal .modal-dialog {
        max-width: 480px;
    }
    #eventModal .modal-content {
        border-radius: 12px;
        border: 1px solid var(--cal-border);
        overflow: hidden;
    }
    #eventModal .modal-header {
        background: var(--cal-surface-muted);
        border-bottom: 1px solid var(--cal-border);
        padding: 18px 20px;
        align-items: flex-start;
        gap: 12px;
    }
    .event-identity {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }
    .event-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--cal-ink);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .event-identity-text { min-width: 0; }
    #eventModalTitle {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cal-ink);
        margin: 0 0 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
    .status-pill .dot { width: 6px; height: 6px; border-radius: 50%; }
    .status-pill.pickup { background: var(--cal-accent-soft); color: var(--cal-accent); }
    .status-pill.pickup .dot { background: var(--cal-accent); }
    .status-pill.overdue { background: var(--cal-danger-soft); color: var(--cal-danger); }
    .status-pill.overdue .dot { background: var(--cal-danger); }
    .status-pill.returned { background: var(--cal-neutral-soft); color: var(--cal-neutral); }
    .status-pill.returned .dot { background: var(--cal-neutral); }

    #eventModal .modal-body {
        padding: 20px;
    }
    .modal-section {
        margin-bottom: 20px;
    }
    .modal-section:last-child { margin-bottom: 0; }
    .modal-section-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--cal-ink-faint);
        margin-bottom: 10px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 16px;
    }
    .info-field { min-width: 0; }
    .info-field.span-2 { grid-column: 1 / -1; }
    .info-field-label {
        font-size: 0.7rem;
        color: var(--cal-ink-faint);
        margin-bottom: 2px;
    }
    .info-field-value {
        font-size: 0.88rem;
        color: var(--cal-ink);
        font-weight: 500;
        word-break: break-word;
    }

    .modal-item-row {
        display: flex; justify-content: space-between; align-items: center;
        border: 1px solid var(--cal-border-soft); border-radius: 7px;
        padding: 9px 12px; margin-bottom: 6px; font-size: 0.85rem;
        background: var(--cal-surface);
        transition: border-color 0.12s ease;
    }
    .modal-item-row:hover { border-color: var(--cal-border); }
    .modal-item-name { font-weight: 500; color: var(--cal-ink); }
    .modal-item-meta { display: block; font-size: 0.72rem; color: var(--cal-ink-faint); margin-top: 1px; }
    .modal-item-price { font-weight: 600; color: var(--cal-ink); white-space: nowrap; margin-left: 12px; }
    .modal-sub-item {
        font-size: 0.76rem; color: var(--cal-ink-soft);
        padding: 4px 0 4px 16px;
        border-left: 2px solid var(--cal-border-soft);
        margin: 0 0 2px 8px;
    }

    .modal-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--cal-surface-muted);
        border: 1px solid var(--cal-border);
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 4px;
    }
    .modal-total-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--cal-ink-soft);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .modal-total-value {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--cal-ink);
    }

    #eventModal .modal-footer {
        border-top: 1px solid var(--cal-border-soft);
        padding: 14px 20px;
    }
    #eventModal .btn-outline-secondary {
        border-radius: 6px;
        font-size: 0.85rem;
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 991.98px) {
        .calendar-card-body { padding: 14px; }
        .fc-daygrid-day-frame { min-height: 84px; }
    }

    @media (max-width: 767.98px) {
        .page-header { align-items: flex-start; }
        .calendar-card-header { flex-direction: column; align-items: flex-start; }
        .fc .fc-toolbar { flex-direction: column; align-items: stretch; gap: 10px; }
        .fc .fc-toolbar-chunk { display: flex; justify-content: center; }
        .fc .fc-button { padding: 7px 14px !important; font-size: 0.85rem !important; }
        .fc-daygrid-day-frame { min-height: 70px; padding: 5px; }
        .fc-event { font-size: 0.68rem; }
        .info-grid { grid-template-columns: 1fr; }
        #eventModalTitle { max-width: 170px; }
    }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
    <main class="flex-fill">
        <div class="container-fluid p-4">

            <div class="page-header">
                <div>
                    <h1>Booking Calendar</h1>
                    <p>See every scheduled pickup and return in one view.</p>
                </div>
                <div class="header-stats">
                    <span class="stat-chip"><span class="dot active"></span>Active <strong><?= (int)$activeCount ?></strong></span>
                    <span class="stat-chip"><span class="dot overdue"></span>Overdue <strong><?= (int)$overdueCount ?></strong></span>
                    <span class="stat-chip"><span class="dot returned"></span>Returned <strong><?= (int)$returnedCount ?></strong></span>
                </div>
            </div>

            <div class="calendar-card">
                <div class="calendar-card-header">
                    <div class="calendar-legend">
                        <span class="legend-item"><span class="legend-swatch legend-book"></span>Book</span>
                        <span class="legend-item"><span class="legend-swatch legend-overdue"></span>Overdue</span>
                        <span class="legend-item"><span class="legend-swatch legend-returned"></span>Returned</span>
                    </div>
                </div>
                <div class="calendar-card-body">
                    <div id="calendar"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="event-identity">
                    <div class="event-avatar" id="eventAvatar">--</div>
                    <div class="event-identity-text">
                        <h5 class="modal-title" id="eventModalTitle">Booking details</h5>
                        <span class="status-pill" id="eventKindBadge"><span class="dot"></span><span class="label"></span></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="modal-section">
                    <div class="modal-section-label">Booking information</div>
                    <div class="info-grid">
                        <div class="info-field">
                            <div class="info-field-label">Customer</div>
                            <div class="info-field-value" id="eventCustomer"></div>
                        </div>
                        <div class="info-field">
                            <div class="info-field-label">Phone</div>
                            <div class="info-field-value" id="eventPhone"></div>
                        </div>
                        <div class="info-field span-2">
                            <div class="info-field-label">Address</div>
                            <div class="info-field-value" id="eventAddress"></div>
                        </div>
                        <div class="info-field">
                            <div class="info-field-label">Pickup date &amp; time</div>
                            <div class="info-field-value" id="eventDate"></div>
                        </div>
                        <div class="info-field">
                            <div class="info-field-label">Return date</div>
                            <div class="info-field-value" id="eventReturnDate"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-label">Order summary</div>
                    <div id="eventItems"></div>
                </div>

                <div class="modal-total-row">
                    <span class="modal-total-label">Total</span>
                    <span class="modal-total-value" id="eventTotal"></span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/font/js/all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js"></script>
<script>
const bookingEvents = <?= json_encode($events) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        height: 'auto',
        events: bookingEvents,
        eventClassNames: function(arg) {
            const p = arg.event.extendedProps;
            if (p.status === 'Returned') return ['returned-event'];
            if (p.overdue) return ['overdue-event'];
            return [];
        },
        eventClick: function(info) {
            const p = info.event.extendedProps;

            const initials = (p.customer || '?')
                .trim()
                .split(/\s+/)
                .slice(0, 2)
                .map(s => s.charAt(0).toUpperCase())
                .join('') || '?';

            document.getElementById('eventAvatar').textContent = initials;
            document.getElementById('eventModalTitle').textContent = p.customer;
            document.getElementById('eventCustomer').textContent = p.customer;
            document.getElementById('eventPhone').textContent = p.phone || 'No phone';
            document.getElementById('eventAddress').textContent = p.address || 'No address';
            document.getElementById('eventReturnDate').textContent = p.return_date;
            document.getElementById('eventDate').textContent = p.date + ' — ' + p.time;

            const badge = document.getElementById('eventKindBadge');
            const badgeLabel = badge.querySelector('.label');
            if (p.status === 'Returned') {
                badgeLabel.textContent = 'Returned';
                badge.className = 'status-pill returned';
            } else if (p.overdue) {
                badgeLabel.textContent = 'Borrowed (Overdue)';
                badge.className = 'status-pill overdue';
            } else {
                badgeLabel.textContent = 'Borrowed';
                badge.className = 'status-pill pickup';
            }
            badge.innerHTML = '<span class="dot"></span><span class="label">' + badgeLabel.textContent + '</span>';

            const itemsEl = document.getElementById('eventItems');
            itemsEl.innerHTML = '';
            p.items.forEach(function(it) {
                const row = document.createElement('div');
                row.className = 'modal-item-row';
                const typeLabel = it.type === 'package' ? 'Package' : 'Equipment';
                const qtyLabel = it.quantity + ' pc' + (it.quantity > 1 ? 's' : '');
                const price = '₱' + (it.price * it.quantity).toFixed(2);
                row.innerHTML =
                    '<span class="modal-item-name">' + it.name +
                    '<span class="modal-item-meta">' + typeLabel + ' · ' + qtyLabel + '</span></span>' +
                    '<span class="modal-item-price">' + price + '</span>';
                itemsEl.appendChild(row);

                if (it.type === 'package' && it.sub_items && it.sub_items.length) {
                    it.sub_items.forEach(function(sub) {
                        const subRow = document.createElement('div');
                        subRow.className = 'modal-sub-item';
                        subRow.textContent = sub;
                        itemsEl.appendChild(subRow);
                    });
                }
            });

            document.getElementById('eventTotal').textContent = '₱' + Number(p.total_amount).toFixed(2);
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }
    });
    calendar.render();
});
</script>
</body>
</html>