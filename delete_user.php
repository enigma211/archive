<?php
/**
 * Delete User (Admin Only)
 * Securely deletes user accounts with proper validation
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

// Get current user ID
$current_user_id = getCurrentUserId();

// Security check: prevent admin from deleting themselves
if ($user_id == $current_user_id) {
    header('Location: manage_users.php?error=' . urlencode('شما نمی‌توانید حساب کاربری خود را حذف کنید'));
    exit();
}

// Connect to database
$conn = getConnection();

if (!$conn) {
    header('Location: manage_users.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Check if user exists
    $query = "SELECT id, username, role FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: manage_users.php?error=' . urlencode('کاربر مورد نظر یافت نشد'));
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Additional security check: prevent deleting the last admin
    if ($user['role'] === 'admin') {
        $query = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['admin_count'] <= 1) {
            header('Location: manage_users.php?error=' . urlencode('نمی‌توان آخرین مدیر سیستم را حذف کرد'));
            exit();
        }
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if user has any case entries
    $query = "SELECT COUNT(*) as entry_count FROM case_entries WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['entry_count'] > 0) {
        // If user has entries, we need to handle them
        // Option 1: Transfer entries to current admin
        $query = "UPDATE case_entries SET user_id = :admin_id WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':admin_id', $current_user_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("خطا در انتقال ورودی‌های کاربر");
        }
    }
    
    // Delete the user
    $query = "DELETE FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در حذف کاربر");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to manage users page
    header('Location: manage_users.php?success=' . urlencode('کاربر ' . $user['username'] . ' با موفقیت حذف شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Delete user error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: manage_users.php?error=' . urlencode('خطا در حذف کاربر: ' . $e->getMessage()));
    exit();
}
?>
