<?php
/**
 * Unlock User (Admin Only)
 * Allows admins to unlock locked user accounts
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

// Get user ID from URL
$user_id = $_GET['id'] ?? '';

if (empty($user_id) || !is_numeric($user_id)) {
    header('Location: manage_users.php?error=' . urlencode('شناسه کاربر نامعتبر است'));
    exit();
}

// Connect to database
$conn = getConnection();

if (!$conn) {
    header('Location: manage_users.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Check if user exists and is locked
    $query = "SELECT id, username, locked_until FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("کاربر مورد نظر یافت نشد");
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is actually locked
    if (!$user['locked_until'] || strtotime($user['locked_until']) <= time()) {
        throw new Exception("این کاربر قفل نشده است");
    }
    
    // Unlock the user
    $query = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_failed_login = NULL WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در باز کردن قفل کاربر");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to manage users page
    header('Location: manage_users.php?success=' . urlencode('قفل کاربر ' . $user['username'] . ' با موفقیت باز شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Unlock user error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: manage_users.php?error=' . urlencode('خطا در باز کردن قفل: ' . $e->getMessage()));
    exit();
}
?>
