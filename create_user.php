<?php
/**
 * Create User Handler (Admin Only)
 * Handles new user creation form submission
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_users.php?error=invalid_request');
    exit();
}

// Get form data
$username = trim($_POST['username'] ?? '');
$display_name = trim($_POST['display_name'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? '';

// Validate input
$errors = [];

if (empty($username)) {
    $errors[] = 'نام کاربری اجباری است';
} elseif (strlen($username) < 3) {
    $errors[] = 'نام کاربری باید حداقل 3 کاراکتر باشد';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'نام کاربری فقط می‌تواند شامل حروف، اعداد و خط تیره باشد';
}

if (!empty($display_name) && strlen($display_name) > 100) {
    $errors[] = 'نام نمایشی نباید بیش از 100 کاراکتر باشد';
}

if (empty($password)) {
    $errors[] = 'رمز عبور اجباری است';
} elseif (strlen($password) < 6) {
    $errors[] = 'رمز عبور باید حداقل 6 کاراکتر باشد';
}

if ($password !== $confirm_password) {
    $errors[] = 'رمز عبور و تأیید آن باید یکسان باشند';
}

if (!in_array($role, ['admin', 'support'])) {
    $errors[] = 'نقش کاربر نامعتبر است';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: add_user.php?error=' . urlencode($error_message));
    exit();
}

// Connect to database
$conn = getConnection();

if (!$conn) {
    header('Location: add_user.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("نام کاربری قبلاً استفاده شده است");
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $query = "INSERT INTO users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, :role)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':display_name', $display_name);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':role', $role);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در ایجاد کاربر");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to manage users page
    header('Location: manage_users.php?success=' . urlencode('کاربر ' . $username . ' با موفقیت ایجاد شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Create user error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: add_user.php?error=' . urlencode('خطا در ایجاد کاربر: ' . $e->getMessage()));
    exit();
}
?>
