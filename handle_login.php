<?php
/**
 * Enhanced Login Handler with Attempt Tracking and Lockout
 * Processes login form submission with security features
 */

require_once 'config.php';
require_once 'includes/SessionHelper.php';

// Start session
SessionHelper::start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=invalid_request');
    exit();
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Get client information
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    header('Location: login.php?error=empty_fields');
    exit();
}

try {
    // Connect to database
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Prepare query to fetch user with lockout information
    $query = "SELECT id, username, display_name, password_hash, role, failed_login_attempts, locked_until, last_failed_login 
              FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // User not found - log attempt
        logLoginAttempt($conn, $username, $ip_address, $user_agent, false);
        $conn->commit();
        header('Location: login.php?error=invalid_credentials');
        exit();
    }
    
    // Get user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $conn->commit();
        $lock_time = date('Y/m/d H:i:s', strtotime($user['locked_until']));
        header('Location: login.php?error=account_locked&lock_time=' . urlencode($lock_time));
        exit();
    }
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Password is correct - successful login
        
        // Reset failed attempts and unlock account
        $query = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_failed_login = NULL WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        
        // Log successful attempt
        logLoginAttempt($conn, $username, $ip_address, $user_agent, true);
        
        // Set session data
        SessionHelper::setUser($user['id'], $user['username'], $user['role'], $user['display_name'] ?? null);
        SessionHelper::set('login_time', time());
        SessionHelper::set('last_activity', time());
        
        $conn->commit();
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        // Password is incorrect - increment failed attempts
        
        $failed_attempts = $user['failed_login_attempts'] + 1;
        $locked_until = null;
        
        // Lock account after 5 failed attempts
        if ($failed_attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }
        
        // Update failed attempts
        $query = "UPDATE users SET failed_login_attempts = :failed_attempts, locked_until = :locked_until, last_failed_login = NOW() WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':failed_attempts', $failed_attempts);
        $stmt->bindParam(':locked_until', $locked_until);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        
        // Log failed attempt
        logLoginAttempt($conn, $username, $ip_address, $user_agent, false);
        
        $conn->commit();
        
        // Check if account is now locked
        if ($failed_attempts >= 5) {
            $lock_time = date('Y/m/d H:i:s', strtotime('+24 hours'));
            header('Location: login.php?error=account_locked&lock_time=' . urlencode($lock_time));
        } else {
            $remaining_attempts = 5 - $failed_attempts;
            header('Location: login.php?error=invalid_credentials&attempts=' . $remaining_attempts);
        }
        exit();
    }
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Login error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: login.php?error=system_error');
    exit();
}

/**
 * Log login attempt to database
 */
function logLoginAttempt($conn, $username, $ip_address, $user_agent, $success) {
    $query = "INSERT INTO login_attempts (username, ip_address, user_agent, success) VALUES (:username, :ip_address, :user_agent, :success)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $user_agent);
    $stmt->bindParam(':success', $success, PDO::PARAM_BOOL);
    $stmt->execute();
}
?>
