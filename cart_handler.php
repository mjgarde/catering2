<?php
session_start();
include 'includes/db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cart_key($type, $id) {
    return $type . '_' . $id;
}

function build_cart_response($conn) {
    $items = [];
    foreach ($_SESSION['cart'] as $entry) {
        if ($entry['type'] === 'equipment') {
            $stmt = $conn->prepare("SELECT id, name, price, photo, stock FROM equipments WHERE id = ?");
            $stmt->bind_param("i", $entry['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) continue;
            $items[] = [
                'type' => 'equipment',
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'photo' => $row['photo'],
                'stock' => (int)$row['stock'],
                'qty' => (int)$entry['qty'],
            ];
        } elseif ($entry['type'] === 'package') {
            $stmt = $conn->prepare("SELECT id, package_name, price FROM packages WHERE id = ?");
            $stmt->bind_param("i", $entry['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) continue;
            $items[] = [
                'type' => 'package',
                'id' => (int)$row['id'],
                'name' => $row['package_name'],
                'price' => (float)$row['price'],
                'photo' => null,
                'stock' => null,
                'qty' => (int)$entry['qty'],
            ];
        }
    }
    return $items;
}

$action = $_POST['action'] ?? '';

if ($action === 'get') {
    echo json_encode(['success' => true, 'cart' => build_cart_response($conn)]);
    exit();
}

if ($action === 'add') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!in_array($type, ['equipment', 'package'], true) || $id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit();
    }

    if ($type === 'equipment') {
        $stmt = $conn->prepare("SELECT stock FROM equipments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
            exit();
        }
        if ((int)$row['stock'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Item is out of stock.']);
            exit();
        }
    } else {
        $stmt = $conn->prepare("SELECT id FROM packages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Package not found.']);
            exit();
        }
    }

    $key = cart_key($type, $id);
    $found = false;
    foreach ($_SESSION['cart'] as &$entry) {
        if ($entry['type'] === $type && (int)$entry['id'] === $id) {
            $entry['qty'] += 1;
            $found = true;
            break;
        }
    }
    unset($entry);
    if (!$found) {
        $_SESSION['cart'][] = ['type' => $type, 'id' => $id, 'qty' => 1];
    }

    echo json_encode(['success' => true, 'cart' => build_cart_response($conn)]);
    exit();
}

if ($action === 'update') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $qty = max(1, $qty);

    foreach ($_SESSION['cart'] as &$entry) {
        if ($entry['type'] === $type && (int)$entry['id'] === $id) {
            $entry['qty'] = $qty;
            break;
        }
    }
    unset($entry);

    echo json_encode(['success' => true, 'cart' => build_cart_response($conn)]);
    exit();
}

if ($action === 'remove') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($entry) use ($type, $id) {
        return !($entry['type'] === $type && (int)$entry['id'] === $id);
    }));

    echo json_encode(['success' => true, 'cart' => build_cart_response($conn)]);
    exit();
}

if ($action === 'checkout') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $borrowDate = $_POST['borrow_date'] ?? '';
    $returnDate = $_POST['return_date'] ?? '';

    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Your order is empty.']);
        exit();
    }
    if (empty($customerName) || empty($phone) || empty($address) || empty($borrowDate) || empty($returnDate)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit();
    }
    if (strtotime($returnDate) <= strtotime($borrowDate)) {
        echo json_encode(['success' => false, 'message' => 'Return date must be after the borrow date.']);
        exit();
    }

    $lines = [];
    $total = 0;

    foreach ($_SESSION['cart'] as $entry) {
        if ($entry['type'] === 'equipment') {
            $stmt = $conn->prepare("SELECT id, name, price, stock FROM equipments WHERE id = ?");
        } else {
            $stmt = $conn->prepare("SELECT id, package_name AS name, price FROM packages WHERE id = ?");
        }
        $stmt->bind_param("i", $entry['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) continue;

        if ($entry['type'] === 'equipment' && (int)$row['stock'] < (int)$entry['qty']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock for ' . $row['name'] . '.']);
            exit();
        }

        $lines[] = [
            'type' => $entry['type'],
            'id' => (int)$row['id'],
            'qty' => (int)$entry['qty'],
            'price' => (float)$row['price'],
        ];
        $total += $row['price'] * $entry['qty'];
    }

    if (empty($lines)) {
        echo json_encode(['success' => false, 'message' => 'Your order is empty.']);
        exit();
    }

    // Also validate package contents have enough stock, since stock is only
    // reserved (not deducted) at this stage — staff will deduct on confirmation.
    foreach ($lines as $line) {
        if ($line['type'] === 'package') {
            $pkgItemsStmt = $conn->prepare("SELECT pi.quantity, e.name, e.stock FROM package_items pi JOIN equipments e ON pi.equipment_id = e.id WHERE pi.package_id = ?");
            $pkgItemsStmt->bind_param("i", $line['id']);
            $pkgItemsStmt->execute();
            $pkgItemsRes = $pkgItemsStmt->get_result();
            while ($pi = $pkgItemsRes->fetch_assoc()) {
                $needed = (int)$pi['quantity'] * $line['qty'];
                if ((int)$pi['stock'] < $needed) {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock for ' . $pi['name'] . ' in the selected package.']);
                    exit();
                }
            }
            $pkgItemsStmt->close();
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO customer_booking (customer_name, email, phone, address, borrow_date, return_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("ssssssd", $customerName, $email, $phone, $address, $borrowDate, $returnDate, $total);
        $stmt->execute();
        $bookingId = $stmt->insert_id;
        $stmt->close();

        foreach ($lines as $line) {
            if ($line['type'] === 'equipment') {
                $itemStmt = $conn->prepare("INSERT INTO booking_items (booking_id, equipment_id, package_id, quantity, price) VALUES (?, ?, NULL, ?, ?)");
                $itemStmt->bind_param("iiid", $bookingId, $line['id'], $line['qty'], $line['price']);
                $itemStmt->execute();
                $itemStmt->close();
            } else {
                $itemStmt = $conn->prepare("INSERT INTO booking_items (booking_id, equipment_id, package_id, quantity, price) VALUES (?, NULL, ?, ?, ?)");
                $itemStmt->bind_param("iiid", $bookingId, $line['id'], $line['qty'], $line['price']);
                $itemStmt->execute();
                $itemStmt->close();
            }
        }

        // Stock is intentionally NOT deducted here. It is reserved only once
        // staff confirms the booking in the staff dashboard, which deducts
        // the equipment/package stock at that point.

        $conn->commit();
        $_SESSION['cart'] = [];

        echo json_encode(['success' => true, 'booking_id' => $bookingId]);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Some items are no longer available in the requested quantity.']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);