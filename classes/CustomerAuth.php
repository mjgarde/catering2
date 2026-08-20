<?php
class CustomerAuth {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, fullname, username, password_hash FROM customer_info WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['customer_id'] = $user['id'];
                $_SESSION['customer_name'] = $user['fullname'];
                $_SESSION['customer_username'] = $user['username'];
                return true;
            }
        }
        return false;
    }

    public function logout() {
        unset($_SESSION['customer_id']);
        unset($_SESSION['customer_name']);
        unset($_SESSION['customer_username']);
        unset($_SESSION['cart']);
    }

    public function isLoggedIn() {
        return isset($_SESSION['customer_id']);
    }

    public function getCustomerId() {
        return $_SESSION['customer_id'] ?? null;
    }

    public function getCustomerName() {
        return $_SESSION['customer_name'] ?? null;
    }
}