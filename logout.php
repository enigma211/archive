<?php
/**
 * Logout Script
 * Handles user logout and session destruction
 */

require_once 'includes/SessionHelper.php';

// Start session if not already started
SessionHelper::start();

// Clear all session data
SessionHelper::clearUser();

// Destroy the session completely
SessionHelper::destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: login.php?message=logged_out');
exit();
?>