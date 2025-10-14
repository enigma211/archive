<?php
/**
 * Authentication Class
 * Handles user login, logout, and session management
 */

require_once __DIR__ . '/../config.php';
require_once 'SessionHelper.php';

class Auth {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        global $pdo;
        $this->conn = $pdo;
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        $query = "SELECT id, username, password_hash, role FROM " . $this->table_name . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                SessionHelper::setUser($row['id'], $row['username'], $row['role']);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Logout user
     */
    public function logout() {
        SessionHelper::clearUser();
        SessionHelper::destroy();
        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return SessionHelper::isLoggedIn();
    }

    /**
     * Get current user data
     */
    public function getCurrentUser() {
        return SessionHelper::getCurrentUser();
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Check if user has support role
     */
    public function isSupport() {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'support';
    }

    /**
     * Check if user can edit individuals (admin or support)
     */
    public function canEditIndividuals() {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], ['admin', 'support']);
    }

    /**
     * Check if user can edit case entries (admin or support)
     */
    public function canEditCaseEntries() {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], ['admin', 'support']);
    }

    /**
     * Check if user can edit cases (admin or support)
     */
    public function canEditCases() {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], ['admin', 'support']);
    }

    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    /**
     * Require admin role - redirect if not admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: dashboard.php');
            exit();
        }
    }

    /**
     * Require edit permissions for individuals - redirect if not admin or support
     */
    public function requireEditIndividuals() {
        $this->requireLogin();
        if (!$this->canEditIndividuals()) {
            header('Location: dashboard.php?error=' . urlencode('شما مجوز ویرایش ندارید'));
            exit();
        }
    }

    /**
     * Require edit permissions for case entries - redirect if not admin or support
     */
    public function requireEditCaseEntries() {
        $this->requireLogin();
        if (!$this->canEditCaseEntries()) {
            header('Location: dashboard.php?error=' . urlencode('شما مجوز ویرایش ندارید'));
            exit();
        }
    }

    /**
     * Require edit permissions for cases - redirect if not admin or support
     */
    public function requireEditCases() {
        $this->requireLogin();
        if (!$this->canEditCases()) {
            header('Location: dashboard.php?error=' . urlencode('شما مجوز ویرایش ندارید'));
            exit();
        }
    }
}
?>
