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
    .calendar-card { border: 1px solid #E1E4E8; border-radius: 8px; overflow: hidden; }
    #calendar { max-width: 100%; }

    a, a:hover, a:focus { text-decoration: none !important; }
    .fc a { text-decoration: none !important; }

    .fc { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; }

    .fc .fc-toolbar {
        padding: 4px 4px 16px;
    }
    .fc .fc-toolbar-title {
        font-size: 1.05rem !important;
        font-weight: 600 !important;
        color: #1F2328;
    }
    .fc .fc-button {
        background: transparent !important;
        border: 1px solid #D0D7DE !important;
        color: #57606A !important;
        font-weight: 500 !important;
        font-size: 0.82rem !important;
        box-shadow: none !important;
        text-transform: capitalize !important;
        border-radius: 6px !important;
        padding: 5px 12px !important;
    }
    .fc .fc-button:hover {
        background: #F6F8FA !important;
        color: #1F2328 !important;
        border-color: #D0D7DE !important;
    }
    .fc .fc-button-active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: #1F2328 !important;
        border-color: #1F2328 !important;
        color: #fff !important;
    }
    .fc .fc-button-group { gap: 2px; }

    .fc-theme-standard td, .fc-theme-standard th {
        border: 1px solid #EDEFF2 !important;
    }
    .fc-theme-standard .fc-scrollgrid {
        border: none !important;
        border-top: 1px solid #EDEFF2 !important;
    }

    .fc .fc-col-header-cell {
        background: #FAFBFC;
        padding: 10px 0 !important;
        border-bottom: 1px solid #E1E4E8 !important;
    }
    .fc .fc-col-header-cell-cushion {
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #6E7781;
        pointer-events: none;
    }
    .fc-day-sun .fc-col-header-cell-cushion { color: #CF222E !important; }

    .fc-daygrid-day-frame {
        min-height: 92px;
        padding: 6px;
    }
    .fc .fc-daygrid-day-top {
        justify-content: flex-start;
    }
    .fc .fc-daygrid-day-number {
        text-decoration: none !important;
        font-weight: 500;
        font-size: 0.84rem;
        color: #1F2328;
        padding: 2px 6px;
    }
    .fc-day-sun .fc-daygrid-day-number { color: #CF222E; }
    .fc-day-other .fc-daygrid-day-number { color: #C4C9D0; }

    .fc .fc-day-today {
        background: #FBFCFF !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        background: #1F6FEB;
        color: #ffffff !important;
        border-radius: 6px;
        font-weight: 700;
    }

    .fc-event {
        cursor: pointer;
        font-size: 0.7rem;
        background: #EEF3FC !important;
        color: #1F2328 !important;
        border: none !important;
        border-left: 3px solid #1F6FEB !important;
        border-radius: 4px !important;
        padding: 2px 6px !important;
        margin-bottom: 2px !important;
        text-decoration: none !important;
        font-weight: 500;
    }
    .fc-event:hover {
        background: #DEEAFB !important;
    }
    .fc-event.overdue-event {
        background: #FDECEA !important;
        border-left-color: #CF222E !important;
        color: #CF222E !important;
    }
    .fc-event.overdue-event:hover {
        background: #FADBD8 !important;
    }
    .fc-event.returned-event {
        background: #F1F3F5 !important;
        border-left-color: #8A94A6 !important;
        color: #6E7781 !important;
    }
    .fc-event.returned-event:hover {
        background: #E7E9EC !important;
    }
    .fc-event .fc-event-title {
        text-decoration: none !important;
    }
    .fc-daygrid-event-dot { display: none; }

    .modal-item-row {
        display: flex; justify-content: space-between; align-items: center;
        border: 1px solid #E1E4E8; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; font-size: 0.88rem;
    }
    .modal-sub-item { font-size: 0.78rem; color: #6E7781; padding-left: 14px; }
    .pickup-badge { background: #1F6FEB; color: #fff; }
    .return-badge { background: #57606A; color: #fff; }
    .overdue-badge { background: #CF222E; color: #fff; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
    <main class="flex-fill">
        <div class="container-fluid p-4">
            <div class="mb-4">
                <h1 class="fw-bold mb-1" style="font-size:1.6rem;color:#1C231E;">Booking Calendar</h1>
                <p class="text-muted mb-0">See every scheduled pickup and return in one view</p>
            </div>

            <div class="d-flex gap-3 mb-3 small" style="color:#57606A;">
                <span><span style="display:inline-block;width:10px;height:10px;background:#EEF3FC;border-left:3px solid #1F6FEB;margin-right:5px;"></span>Book</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#FDECEA;border-left:3px solid #CF222E;margin-right:5px;"></span>Overdue</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#F1F3F5;border-left:3px solid #8A94A6;margin-right:5px;"></span>Returned</span>
            </div>

            <div class="card calendar-card">
                <div class="card-body p-3">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:10px;border:1px solid #E1E4E8;">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Booking details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Customer:</strong> <span id="eventCustomer"></span></div>
                <div class="mb-2"><strong>Phone:</strong> <span id="eventPhone"></span></div>
                <div class="mb-2"><strong>Address:</strong> <span id="eventAddress"></span></div>
                <div class="mb-2"><strong>Return date:</strong> <span id="eventReturnDate"></span></div>
                <div class="mb-3">
                    <span class="badge" id="eventKindBadge"></span>
                    <span id="eventDate" class="ms-2 text-muted small"></span>
                </div>
                <hr>
                <strong class="d-block mb-2">Order</strong>
                <div id="eventItems"></div>
                <div class="d-flex justify-content-between mt-3 pt-2" style="border-top:1px solid #333333;">
                    <strong>Total</strong>
                    <strong id="eventTotal"></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;" data-bs-dismiss="modal">Close</button>
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

            document.getElementById('eventModalTitle').textContent = p.customer;
            document.getElementById('eventCustomer').textContent = p.customer;
            document.getElementById('eventPhone').textContent = p.phone || 'No phone';
            document.getElementById('eventAddress').textContent = p.address || 'No address';
            document.getElementById('eventReturnDate').textContent = p.return_date;
            document.getElementById('eventDate').textContent = p.date + ' — ' + p.time;

            const badge = document.getElementById('eventKindBadge');
            if (p.status === 'Returned') {
                badge.textContent = 'Returned';
                badge.className = 'badge return-badge';
            } else if (p.overdue) {
                badge.textContent = 'Borrowed (Overdue)';
                badge.className = 'badge overdue-badge';
            } else {
                badge.textContent = 'Borrowed';
                badge.className = 'badge pickup-badge';
            }

            const itemsEl = document.getElementById('eventItems');
            itemsEl.innerHTML = '';
            p.items.forEach(function(it) {
                const row = document.createElement('div');
                row.className = 'modal-item-row';
                const label = it.name + (it.type === 'package' ? ' (Package)' : '') + ' — ' + it.quantity + ' pc' + (it.quantity > 1 ? 's' : '');
                const price = '₱' + (it.price * it.quantity).toFixed(2);
                row.innerHTML = '<span>' + label + '</span><span>' + price + '</span>';
                itemsEl.appendChild(row);

                if (it.type === 'package' && it.sub_items && it.sub_items.length) {
                    it.sub_items.forEach(function(sub) {
                        const subRow = document.createElement('div');
                        subRow.className = 'modal-sub-item';
                        subRow.textContent = '- ' + sub;
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