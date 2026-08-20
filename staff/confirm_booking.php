<?php
session_start();
include '../includes/db_connection.php';
include '../classes/StaffAuth.php';
header('Content-Type: application/json');

$staffAuth = new StaffAuth($conn);
if (!$staffAuth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT id, status FROM customer_booking WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Booking not found.');
    }
    $booking = $result->fetch_assoc();
    $stmt->close();

    if ($booking['status'] !== 'Pending') {
        throw new Exception('This booking has already been confirmed or processed.');
    }

    $stmt = $conn->prepare("SELECT equipment_id, package_id, quantity FROM booking_items WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $items = $stmt->get_result();

    $deductions = [];
    while ($item = $items->fetch_assoc()) {
        if ($item['equipment_id']) {
            $eqId = (int)$item['equipment_id'];
            $deductions[$eqId] = ($deductions[$eqId] ?? 0) + (int)$item['quantity'];
        } elseif ($item['package_id']) {
            $pkgStmt = $conn->prepare("SELECT equipment_id, quantity FROM package_items WHERE package_id = ?");
            $pkgStmt->bind_param("i", $item['package_id']);
            $pkgStmt->execute();
            $pkgItems = $pkgStmt->get_result();
            while ($pi = $pkgItems->fetch_assoc()) {
                $eqId = (int)$pi['equipment_id'];
                $needed = (int)$pi['quantity'] * (int)$item['quantity'];
                $deductions[$eqId] = ($deductions[$eqId] ?? 0) + $needed;
            }
            $pkgStmt->close();
        }
    }
    $stmt->close();

    foreach ($deductions as $eqId => $needed) {
        $checkStmt = $conn->prepare("SELECT name, stock FROM equipments WHERE id = ?");
        $checkStmt->bind_param("i", $eqId);
        $checkStmt->execute();
        $eqRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$eqRow) {
            throw new Exception("Equipment not found.");
        }
        if ((int)$eqRow['stock'] < $needed) {
            throw new Exception("Insufficient stock for {$eqRow['name']}. Available: {$eqRow['stock']}, Needed: {$needed}");
        }

        $updateStmt = $conn->prepare("UPDATE equipments SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $updateStmt->bind_param("iii", $needed, $eqId, $needed);
        $updateStmt->execute();
        if ($updateStmt->affected_rows === 0) {
            throw new Exception("Stock changed before confirmation could complete.");
        }
        $updateStmt->close();
    }

    $updateBooking = $conn->prepare("UPDATE customer_booking SET status = 'Borrowed' WHERE id = ?");
    $updateBooking->bind_param("i", $booking_id);
    $updateBooking->execute();
    $updateBooking->close();

    $conn->commit();
    echo json_encode(['success' => true]);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}