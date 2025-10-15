<?php
/**
 * Update User Handler (Admin Only)
 * Handles user update form submission
 */

require_once 'header.php';
require_once 'includes/AuditLogger.php';

// Check if user is admin
requireAdmin();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_users.php?error=invalid_request');
    exit();
}

// Get form data
$user_id = $_POST['user_id'] ?? '';
$display_name = trim($_POST['display_name'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? '';

// Validate input
$errors = [];

if (empty($user_id) || !is_numeric($user_id)) {
    $errors[] = 'شناسه کاربر نامعتبر است';
}

if (!empty($display_name) && strlen($display_name) > 100) {
    $errors[] = 'نام نمایشی نباید بیش از 100 کاراکتر باشد';
}

if (!in_array($role, ['admin', 'support'])) {
    $errors[] = 'نقش کاربر نامعتبر است';
}

if (!empty($new_password)) {
    if (strlen($new_password) < 6) {
        $errors[] = 'رمز عبور باید حداقل 6 کاراکتر باشد';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'رمز عبور و تأیید آن باید یکسان باشند';
    }
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: edit_user.php?id=' . $user_id . '&error=' . urlencode($error_message));
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
    
    // Check if user exists
    $query = "SELECT id, username FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("کاربر مورد نظر یافت نشد");
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build update query
    $update_fields = ['role = :role', 'display_name = :display_name'];
    
    // Update password if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields[] = 'password_hash = :password_hash';
    }
    
    // Update user
    $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':display_name', $display_name);
    
    if (!empty($new_password)) {
        $stmt->bindParam(':password_hash', $hashed_password);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در به‌روزرسانی اطلاعات کاربر");
    }
    
    // Audit: user updated
    $changes = ['role' => $role, 'display_name' => $display_name];
    if (!empty($new_password)) {
        $changes['password_changed'] = true;
    }
    AuditLogger::log('user_update', 'user', $user_id, [
        'username' => $user['username'],
        'changes' => $changes
    ]);
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to manage users page
    header('Location: manage_users.php?success=' . urlencode('اطلاعات کاربر ' . $user['username'] . ' با موفقیت به‌روزرسانی شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Update user error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: edit_user.php?id=' . $user_id . '&error=' . urlencode('خطا در به‌روزرسانی کاربر: ' . $e->getMessage()));
    exit();
}
?>
