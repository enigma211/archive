<?php
/**
 * Helper Functions for Role-Based Access Control (RBAC)
 * Provides utility functions for user authentication and authorization
 */

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if current user is an admin
 * @return bool True if user is logged in and has admin role, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is a support user
 * @return bool True if user is logged in and has support role, false otherwise
 */
function isSupport() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'support';
}

/**
 * Get current user's role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Get current user's ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user's username
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Check if user has permission to perform admin actions
 * @return bool True if user can perform admin actions
 */
function canPerformAdminActions() {
    return isAdmin();
}

/**
 * Redirect to login page if user is not logged in
 * @param string $redirect_url Optional URL to redirect to after login
 */
function requireLogin($redirect_url = null) {
    if (!isLoggedIn()) {
        $redirect = $redirect_url ? '?redirect=' . urlencode($redirect_url) : '';
        header('Location: login.php' . $redirect);
        exit();
    }
}

/**
 * Redirect to dashboard with access denied message if user is not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php?error=' . urlencode('شما دسترسی لازم برای انجام این عملیات را ندارید'));
        exit();
    }
}

/**
 * Check if user can delete a specific user (admin can delete anyone except themselves)
 * @param int $target_user_id ID of user to be deleted
 * @return bool True if current user can delete the target user
 */
function canDeleteUser($target_user_id) {
    if (!isAdmin()) {
        return false;
    }
    
    // Admin cannot delete themselves
    return getCurrentUserId() != $target_user_id;
}

/**
 * Check if user can delete attachments
 * @return bool True if user can delete attachments
 */
function canDeleteAttachments() {
    return isAdmin();
}

/**
 * Check if user can manage users
 * @return bool True if user can manage users
 */
function canManageUsers() {
    return isAdmin();
}

/**
 * Check if user can delete cases
 * @return bool True if user can delete cases
 */
function canDeleteCases() {
    return isAdmin();
}

/**
 * Check if user can delete individuals
 * @return bool True if user can delete individuals
 */
function canDeleteIndividuals() {
    return isAdmin();
}

/**
 * Get database connection
 * @return PDO|null Database connection or null if failed
 */
function getConnection() {
    try {
        require_once 'config.php';
        global $pdo;
        return $pdo;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get system setting value
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value or default
 */
function getSetting($key, $default = '') {
    try {
        $conn = getConnection();
        if (!$conn) {
            return $default;
        }
        
        $query = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['setting_value'];
        }
        
        return $default;
    } catch (Exception $e) {
        error_log("Get setting error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Check if SSL is enabled and redirect if necessary
 */
function checkSSLRedirect() {
    $ssl_enabled = getSetting('ssl_enabled', '0');
    
    if ($ssl_enabled === '1' && !isHTTPS()) {
        // Redirect to HTTPS if SSL is enabled
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL", true, 301);
        exit();
    }
}

/**
 * Check if current request is HTTPS
 * @return bool True if HTTPS, false otherwise
 */
function isHTTPS() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           $_SERVER['SERVER_PORT'] == 443 ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}
?>
