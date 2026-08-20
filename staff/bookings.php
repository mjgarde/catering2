<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connection.php';
include '../classes/StaffAuth.php';
$staffAuth = new StaffAuth($conn);
if (!$staffAuth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}
$staff_firstname = $_SESSION['staff_firstname'] ?? 'Staff';
$conn->query("UPDATE customer_booking 
SET fine_amount = TIMESTAMPDIFF(HOUR, return_date, NOW()) * 100 
WHERE status = 'Borrowed' 
AND NOW() > return_date 
AND TIMESTAMPDIFF(HOUR, return_date, NOW()) > 0");
$bookings_query = "SELECT id, customer_name, email, phone, address, borrow_date, return_date, 
                   actual_return_date, total_amount, fine_amount, damage_fee, damage_notes,
                   status, created_at, sms_reminder_sent,
                   CASE 
                       WHEN status = 'Borrowed' AND NOW() > return_date THEN 'Overdue'
                       ELSE status
                   END as display_status
                   FROM customer_booking 
                   WHERE status IN ('Pending', 'Borrowed')
                   ORDER BY 
                       CASE WHEN status = 'Pending' THEN 0 ELSE 1 END,
                       CASE WHEN NOW() > return_date THEN 0 ELSE 1 END,
                       return_date ASC";
$bookings_result = $conn->query($bookings_query);
$bookings = [];
if ($bookings_result) {
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings - Staff Dashboard</title>
<link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/font/css/all.min.css">
<style>
    .damaged-item {
        border: 1px solid #dc3545;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        background: #fff;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .status-Borrowed { background-color: #0d6efd; color: white; }
    .status-Overdue { background-color: #dc3545; color: white; }
    .status-Pending { background-color: #6c757d; color: white; }
    .booking-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .booking-row:hover { background-color: #f8f9fa; }
    .timer-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .timer-upcoming { background-color: #0dcaf0; color: #000; }
    .timer-soon { background-color: #ffc107; color: #000; }
    .timer-pay { background-color: #dc3545; color: white; font-weight: 700; }
    .sms-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
        margin-left: 5px;
    }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
    <main class="flex-fill">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Active Bookings</h1>
                <span class="text-muted"><i class="fas fa-info-circle"></i> New bookings are placed by customers on the website</span>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Borrow Date</th>
                                    <th>Return Date & Time</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Time / Payment Due</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No active bookings. All clear!</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr class="booking-row" onclick="viewBooking(<?php echo $booking['id']; ?>)" 
                                            data-return-date="<?php echo $booking['return_date']; ?>" 
                                            data-status="<?php echo $booking['status']; ?>"
                                            data-display-status="<?php echo $booking['display_status']; ?>">
                                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($booking['borrow_date'])); ?></td>
                                            <td>
                                                <?php echo date('M d, Y h:i A', strtotime($booking['return_date'])); ?>
                                                <?php if ($booking['sms_reminder_sent'] == 1): ?>
                                                    <span class="badge bg-success sms-badge" title="SMS reminder sent">
                                                        <i class="fas fa-check"></i> SMS Sent
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['display_status']; ?>">
                                                    <?php echo $booking['display_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="timer-badge" id="timer-<?php echo $booking['id']; ?>">
                                                    <i class="fas fa-clock"></i> Calculating...
                                                </span>
                                            </td>
                                            <td onclick="event.stopPropagation();">
                                                <button class="btn btn-sm btn-info" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if ($booking['display_status'] === 'Pending'): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="confirmBooking(<?php echo $booking['id']; ?>, this)">
                                                        <i class="fas fa-check-circle"></i> Confirm
                                                    </button>
                                                <?php elseif ($booking['display_status'] === 'Borrowed'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="openReturnModal(<?php echo $booking['id']; ?>, false)">
                                                        <i class="fas fa-undo"></i> Return
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning" onclick="openReturnModal(<?php echo $booking['id']; ?>, true)">
                                                        <i class="fas fa-money-bill-wave"></i> Settle
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white" id="returnModalHeader">
                <h5 class="modal-title" id="returnModalTitle"><i class="fas fa-undo"></i> Return Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_return.php" method="POST" id="returnForm">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="return_booking_id">
                    
                    <div class="alert alert-info" id="returnAlert">
                        <i class="fas fa-info-circle"></i> Mark this booking as returned
                    </div>

                    <div id="fineAlert" class="alert alert-warning" style="display: none;">
                        <h6 class="mb-2"><i class="fas fa-exclamation-triangle"></i> <strong>Overdue Charges</strong></h6>
                        <p class="mb-1">Rental: <span id="rentalAmount">₱0.00</span></p>
                        <p class="mb-1 text-danger">Overdue Fine: <span id="fineAmount">₱0.00</span></p>
                        <hr>
                        <p class="mb-0"><strong>Subtotal: <span id="subtotalAmount">₱0.00</span></strong></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Any Damages?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_damage" id="no_damage" value="0" checked onchange="toggleDamageFields()">
                            <label class="form-check-label" for="no_damage">No Damage</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="has_damage" id="has_damage" value="1" onchange="toggleDamageFields()">
                            <label class="form-check-label" for="has_damage">Yes, Has Damage</label>
                        </div>
                    </div>

                    <div id="damageFields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Damaged Equipment</label>
                            <div id="damagedEquipmentList" class="border rounded p-3 bg-light">
                                <p class="text-muted mb-2"><small>Loading equipment from this booking...</small></p>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="addDamagedEquipment()">
                                <i class="fas fa-plus"></i> Add Damaged Item
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label for="damage_fee" class="form-label">Total Damage Fee (₱)</label>
                            <input type="number" class="form-control" name="damage_fee" id="damage_fee" min="0" step="0.01" value="0" onchange="updateTotalPayment()">
                        </div>
                        <div class="mb-3">
                            <label for="damage_notes" class="form-label">Overall Damage Description</label>
                            <textarea class="form-control" name="damage_notes" id="damage_notes" rows="3" placeholder="Describe the overall damage situation..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-success" id="totalPaymentAlert" style="display: none;">
                        <h5 class="mb-0"><strong>TOTAL TO COLLECT: <span id="totalPayment">₱0.00</span></strong></h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="returnSubmitBtn">
                        <i class="fas fa-check"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
let currentBookingData = null;
let damagedEquipmentCounter = 0;
let bookingEquipments = [];

function confirmBooking(bookingId, btn) {
    if (!confirm('Confirm this booking? This will deduct the reserved equipment/package quantities from stock.')) {
        return;
    }
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';

    fetch('confirm_booking.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `booking_id=${encodeURIComponent(bookingId)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Unable to confirm booking.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    })
    .catch(() => {
        alert('Something went wrong. Please try again.');
        btn.disabled = false;
        btn.innerHTML = original;
    });
}

function toggleDamageFields() {
    const hasDamage = document.getElementById('has_damage').checked;
    document.getElementById('damageFields').style.display = hasDamage ? 'block' : 'none';
    if (!hasDamage) {
        document.getElementById('damage_fee').value = '0';
        document.getElementById('damage_notes').value = '';
        document.getElementById('damagedEquipmentList').innerHTML = '<p class="text-muted mb-2"><small>Loading equipment from this booking...</small></p>';
        damagedEquipmentCounter = 0;
    } else {
        loadBookingEquipment();
    }
    updateTotalPayment();
}

function loadBookingEquipment() {
    if (!currentBookingData) return;
    
    const bookingId = currentBookingData.id;
    
    fetch(`get_booking_equipment.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bookingEquipments = data.equipment;
                document.getElementById('damagedEquipmentList').innerHTML = '';
                addDamagedEquipment();
            } else {
                document.getElementById('damagedEquipmentList').innerHTML = '<p class="text-danger mb-0">No equipment found in this booking</p>';
            }
        })
        .catch(error => {
            console.error('Error loading equipment:', error);
            document.getElementById('damagedEquipmentList').innerHTML = '<p class="text-danger mb-0">Error loading equipment</p>';
        });
}

function addDamagedEquipment() {
    if (bookingEquipments.length === 0) {
        alert('No equipment available in this booking');
        return;
    }
    
    damagedEquipmentCounter++;
    const listDiv = document.getElementById('damagedEquipmentList');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'damaged-item';
    itemDiv.id = `damaged-${damagedEquipmentCounter}`;
    
    let optionsHTML = '<option value="">-- Select Equipment --</option>';
    bookingEquipments.forEach(eq => {
        optionsHTML += `<option value="${eq.equipment_id}" data-max="${eq.quantity}">${eq.equipment_name} (Booked: ${eq.quantity})</option>`;
    });
    
    itemDiv.innerHTML = `
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small">Equipment</label>
                <select class="form-select form-select-sm" name="damaged_equipment_id[]" onchange="updateMaxQuantity(${damagedEquipmentCounter})" required>
                    ${optionsHTML}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Damaged Quantity</label>
                <input type="number" class="form-control form-control-sm damaged-qty" id="damaged-qty-${damagedEquipmentCounter}" name="damaged_quantity[]" value="1" min="1" required>
                <small class="text-muted" id="max-qty-${damagedEquipmentCounter}"></small>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeDamagedEquipment(${damagedEquipmentCounter})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    listDiv.appendChild(itemDiv);
}

function updateMaxQuantity(counter) {
    const selectElement = document.querySelector(`#damaged-${counter} select`);
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const maxQty = selectedOption.dataset.max || 1;
    
    const qtyInput = document.getElementById(`damaged-qty-${counter}`);
    qtyInput.max = maxQty;
    qtyInput.value = Math.min(qtyInput.value, maxQty);
    
    document.getElementById(`max-qty-${counter}`).textContent = `Max: ${maxQty}`;
}

function removeDamagedEquipment(id) {
    document.getElementById(`damaged-${id}`)?.remove();
}

function updateTotalPayment() {
    if (!currentBookingData) return;
    
    const rental = parseFloat(currentBookingData.total_amount);
    const fine = parseFloat(currentBookingData.fine_amount || 0);
    const damage = parseFloat(document.getElementById('damage_fee').value || 0);
    const total = rental + fine + damage;
    
    document.getElementById('totalPayment').textContent = '₱' + total.toFixed(2);
}

function openReturnModal(bookingId, isOverdue) {
    document.getElementById('return_booking_id').value = bookingId;
    
    document.getElementById('no_damage').checked = true;
    document.getElementById('damage_fee').value = '0';
    document.getElementById('damage_notes').value = '';
    document.getElementById('damageFields').style.display = 'none';
    document.getElementById('damagedEquipmentList').innerHTML = '<p class="text-muted mb-2"><small>Loading equipment from this booking...</small></p>';
    damagedEquipmentCounter = 0;
    
    const modalHeader = document.getElementById('returnModalHeader');
    const modalTitle = document.getElementById('returnModalTitle');
    const returnAlert = document.getElementById('returnAlert');
    const submitBtn = document.getElementById('returnSubmitBtn');
    
    if (isOverdue) {
        modalHeader.className = 'modal-header bg-warning text-dark';
        modalTitle.innerHTML = '<i class="fas fa-money-bill-wave"></i> Settle Overdue Payment';
        returnAlert.className = 'alert alert-warning';
        returnAlert.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>This booking is overdue.</strong> Please collect payment before processing return.';
        submitBtn.className = 'btn btn-warning';
        submitBtn.innerHTML = '<i class="fas fa-money-bill-wave"></i> Collect Payment & Return';
    } else {
        modalHeader.className = 'modal-header bg-success text-white';
        modalTitle.innerHTML = '<i class="fas fa-undo"></i> Return Equipment';
        returnAlert.className = 'alert alert-info';
        returnAlert.innerHTML = '<i class="fas fa-info-circle"></i> Mark this booking as returned';
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Process Return';
    }
    
    fetch(`get_booking_details.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentBookingData = data.booking;
                
                if (data.booking.fine_amount > 0) {
                    document.getElementById('fineAlert').style.display = 'block';
                    document.getElementById('rentalAmount').textContent = '₱' + parseFloat(data.booking.total_amount).toFixed(2);
                    document.getElementById('fineAmount').textContent = '₱' + parseFloat(data.booking.fine_amount).toFixed(2);
                    const subtotal = parseFloat(data.booking.total_amount) + parseFloat(data.booking.fine_amount);
                    document.getElementById('subtotalAmount').textContent = '₱' + subtotal.toFixed(2);
                    document.getElementById('totalPaymentAlert').style.display = 'block';
                    document.getElementById('totalPayment').textContent = '₱' + subtotal.toFixed(2);
                } else {
                    document.getElementById('fineAlert').style.display = 'none';
                    document.getElementById('totalPaymentAlert').style.display = 'none';
                }
            }
        });
    
    const modal = new bootstrap.Modal(document.getElementById('returnModal'));
    modal.show();
}

function viewBooking(bookingId) {
    const modal = new bootstrap.Modal(document.getElementById('viewBookingModal'));
    modal.show();
    fetch(`get_booking_details.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) displayBookingDetails(data.booking, data.items);
            else document.getElementById('bookingDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading booking details</div>';
        })
        .catch(error => {
            document.getElementById('bookingDetailsContent').innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function displayBookingDetails(booking, items) {
    let itemsHTML = '';
    
    items.forEach(item => {
        if (item.type === 'Package') {
            itemsHTML += `
                <tr class="table-success">
                    <td>
                        <i class="fas fa-box-open text-success"></i> 
                        <strong>${item.item_name}</strong>
                        <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="togglePackageItems(${item.id})" id="toggle-btn-${item.id}">
                            <i class="fas fa-chevron-down" id="toggle-icon-${item.id}"></i> Show Items
                        </button>
                    </td>
                    <td>${item.type}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                </tr>
                <tr id="package-items-${item.id}" style="display: none;">
                    <td colspan="4" class="bg-light">
                        <div class="ps-4">
                            <small class="text-muted">
                                <i class="fas fa-spinner fa-spin"></i> Loading package contents...
                            </small>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            itemsHTML += `
                <tr>
                    <td><i class="fas fa-tools text-primary"></i> ${item.item_name}</td>
                    <td>${item.type}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                </tr>
            `;
        }
    });
    
    const now = new Date();
    const returnDate = new Date(booking.return_date);
    const displayStatus = now > returnDate ? 'Overdue' : booking.status;
    
    const grandTotal = parseFloat(booking.total_amount) + parseFloat(booking.fine_amount || 0) + parseFloat(booking.damage_fee || 0);
    
    let damageInfo = '';
    if (booking.damage_notes) {
        damageInfo = `<div class="alert alert-warning mt-3">
            <h6><i class="fas fa-exclamation-triangle"></i> Damage Report</h6>
            <p class="mb-0">${booking.damage_notes}</p>
            <strong>Damage Fee: ₱${parseFloat(booking.damage_fee).toFixed(2)}</strong>
        </div>`;
    }
    
    const smsStatus = booking.sms_reminder_sent == 1 
        ? '<span class="badge bg-success"><i class="fas fa-check"></i> SMS Reminder Sent</span>' 
        : '<span class="badge bg-secondary"><i class="fas fa-clock"></i> SMS Not Sent</span>';
    
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-user"></i> Customer Information</h6>
                <table class="table table-sm">
                    <tr><th>Name:</th><td>${booking.customer_name}</td></tr>
                    <tr><th>Email:</th><td>${booking.email || 'N/A'}</td></tr>
                    <tr><th>Phone:</th><td>${booking.phone || 'N/A'}</td></tr>
                    <tr><th>Address:</th><td>${booking.address || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-calendar"></i> Booking Information</h6>
                <table class="table table-sm">
                    <tr><th>Borrow Date:</th><td>${new Date(booking.borrow_date).toLocaleString()}</td></tr>
                    <tr><th>Return Date:</th><td>${new Date(booking.return_date).toLocaleString()}</td></tr>
                    <tr><th>Status:</th><td><span class="status-badge status-${displayStatus}">${displayStatus}</span></td></tr>
                    <tr><th>SMS Status:</th><td>${smsStatus}</td></tr>
                    <tr><th>Created:</th><td>${new Date(booking.created_at).toLocaleString()}</td></tr>
                </table>
            </div>
        </div>
        
        <h6 class="mt-4"><i class="fas fa-list"></i> Booked Items</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr><th>Item Name</th><th>Type</th><th>Quantity</th><th>Price</th></tr>
                </thead>
                <tbody>${itemsHTML}</tbody>
            </table>
        </div>
        
        ${damageInfo}
        
        <div class="alert alert-${booking.fine_amount > 0 ? 'warning' : 'success'}">
            <h5 class="mb-2"><strong>Payment Breakdown:</strong></h5>
            <p class="mb-1">Rental: ₱${parseFloat(booking.total_amount).toFixed(2)}</p>
            ${booking.fine_amount > 0 ? `<p class="mb-1 text-danger">Overdue Fine (₱100/hour): ₱${parseFloat(booking.fine_amount).toFixed(2)}</p>` : ''}
            ${booking.damage_fee > 0 ? `<p class="mb-1 text-warning">Damage Fee: ₱${parseFloat(booking.damage_fee).toFixed(2)}</p>` : ''}
            <hr>
            <h5 class="mb-0"><strong>TOTAL: ₱${grandTotal.toFixed(2)}</strong></h5>
        </div>
    `;
    document.getElementById('bookingDetailsContent').innerHTML = html;
}

function togglePackageItems(bookingItemId) {
    const itemsRow = document.getElementById(`package-items-${bookingItemId}`);
    const toggleIcon = document.getElementById(`toggle-icon-${bookingItemId}`);
    const toggleBtn = document.getElementById(`toggle-btn-${bookingItemId}`);
    
    if (itemsRow.style.display === 'none') {
        fetch(`get_package_items.php?booking_item_id=${bookingItemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.items.length > 0) {
                    let itemsHTML = '<div class="ps-4"><small class="text-muted"><strong>Package contains:</strong></small><ul class="mb-0 mt-1">';
                    data.items.forEach(item => {
                        itemsHTML += `<li>${item.equipment_name} <span class="badge bg-secondary">${item.quantity}x</span></li>`;
                    });
                    itemsHTML += '</ul></div>';
                    itemsRow.querySelector('td').innerHTML = itemsHTML;
                } else {
                    itemsRow.querySelector('td').innerHTML = '<div class="ps-4"><small class="text-muted">No items found</small></div>';
                }
                itemsRow.style.display = '';
                toggleIcon.className = 'fas fa-chevron-up';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up" id="toggle-icon-' + bookingItemId + '"></i> Hide Items';
            })
            .catch(error => {
                console.error('Error loading package items:', error);
                itemsRow.querySelector('td').innerHTML = '<div class="ps-4"><small class="text-danger">Error loading items</small></div>';
                itemsRow.style.display = '';
            });
    } else {
        itemsRow.style.display = 'none';
        toggleIcon.className = 'fas fa-chevron-down';
        toggleBtn.innerHTML = '<i class="fas fa-chevron-down" id="toggle-icon-' + bookingItemId + '"></i> Show Items';
    }
}

function updateTimers() {
    const rows = document.querySelectorAll('.booking-row');
    const now = new Date();
    
    rows.forEach(row => {
        const returnDate = new Date(row.dataset.returnDate);
        const displayStatus = row.dataset.displayStatus;
        const bookingId = row.getAttribute('onclick').match(/\d+/)[0];
        const timerElement = document.getElementById(`timer-${bookingId}`);
        
        if (!timerElement) return;

        if (displayStatus === 'Pending') {
            timerElement.className = 'timer-badge bg-secondary text-white';
            timerElement.innerHTML = '<i class="fas fa-hourglass-half"></i> Awaiting confirmation';
            return;
        }
        
        const timeDiff = returnDate - now;
        
        if (timeDiff < 0) {
            const totalSeconds = Math.floor(Math.abs(timeDiff) / 1000);
            const hoursOverdue = Math.ceil(totalSeconds / 3600);
            const paymentDue = hoursOverdue * 100;
            
            timerElement.className = 'timer-badge timer-pay';
            timerElement.innerHTML = `<i class="fas fa-money-bill-wave"></i> Pay: ₱${paymentDue.toLocaleString()}`;
            return;
        }
        
        const totalSeconds = Math.floor(timeDiff / 1000);
        const days = Math.floor(totalSeconds / (60 * 60 * 24));
        const hours = Math.floor((totalSeconds % (60 * 60 * 24)) / (60 * 60));
        const minutes = Math.floor((totalSeconds % (60 * 60)) / 60);
        const seconds = totalSeconds % 60;
        
        if (days === 0 && hours < 24) {
            timerElement.className = 'timer-badge timer-soon';
            timerElement.innerHTML = `<i class="fas fa-hourglass-half"></i> ${hours}h ${minutes}m ${seconds}s`;
        } else if (days < 3) {
            timerElement.className = 'timer-badge timer-soon';
            timerElement.innerHTML = `<i class="fas fa-clock"></i> ${days}d ${hours}h ${minutes}m`;
        } else {
            timerElement.className = 'timer-badge timer-upcoming';
            timerElement.innerHTML = `<i class="fas fa-calendar-check"></i> ${days}d ${hours}h`;
        }
    });
}

updateTimers();
setInterval(updateTimers, 1000);
</script>
</body>
</html>