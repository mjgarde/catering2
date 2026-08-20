<?php
class AdminAuth {
    private $conn;
    private $timeout = 1000; 

    public function __construct($db) {
        $this->conn = $db;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

     
        $this->checkTimeout();
    }

    private function checkTimeout() {
        if (isset($_SESSION['admin']) && isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->timeout) {
             
                $this->logout();
            } else {
        
                $_SESSION['last_activity'] = time();
            }
        }
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin'] = $admin['username'];
                $_SESSION['last_activity'] = time(); 
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
     
        if (!isset($_SESSION['admin'])) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->timeout)) {
            $this->logout();
            return false;
        }

   
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function logout() {
        session_destroy();
        unset($_SESSION['admin']);
        unset($_SESSION['last_activity']);
    
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
?>