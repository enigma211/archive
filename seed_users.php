<?php
/**
 * User Seeder Script
 * Creates default users for the complaint management system
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='fa' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ایجاد کاربران پیش‌فرض</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Tahoma', sans-serif; }
        .seeder-result { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>
</head>
<body>
<div class='container mt-5'>
    <h2 class='text-center mb-4'>ایجاد کاربران پیش‌فرض</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("امکان اتصال به پایگاه داده وجود ندارد");
    }
    
    echo "<div class='seeder-result success'>
            <h4><i class='fas fa-check-circle'></i> اتصال به پایگاه داده موفق</h4>
          </div>";
    
    // Define users to create
    $users = [
        [
            'username' => 'admin',
            'password' => 'AdminPassword123!',
            'role' => 'admin'
        ],
        [
            'username' => 'support',
            'password' => 'SupportPassword123!',
            'role' => 'support'
        ]
    ];
    
    $created_users = 0;
    $updated_users = 0;
    
    foreach ($users as $user_data) {
        $username = $user_data['username'];
        $password = $user_data['password'];
        $role = $user_data['role'];
        
        // Check if user already exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // User exists, update password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password_hash = :password_hash, role = :role WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':username', $username);
            
            if ($stmt->execute()) {
                echo "<div class='seeder-result info'>
                        <h4><i class='fas fa-sync'></i> کاربر $username به‌روزرسانی شد</h4>
                        <p>رمز عبور و نقش کاربر $username به‌روزرسانی شد.</p>
                      </div>";
                $updated_users++;
            } else {
                echo "<div class='seeder-result error'>
                        <h4><i class='fas fa-times-circle'></i> خطا در به‌روزرسانی $username</h4>
                      </div>";
            }
        } else {
            // User doesn't exist, create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':role', $role);
            
            if ($stmt->execute()) {
                echo "<div class='seeder-result success'>
                        <h4><i class='fas fa-user-plus'></i> کاربر $username ایجاد شد</h4>
                        <p>کاربر $username با نقش $role ایجاد شد.</p>
                      </div>";
                $created_users++;
            } else {
                echo "<div class='seeder-result error'>
                        <h4><i class='fas fa-times-circle'></i> خطا در ایجاد $username</h4>
                      </div>";
            }
        }
    }
    
    // Display summary
    echo "<div class='seeder-result success'>
            <h4><i class='fas fa-check-circle'></i> خلاصه عملیات</h4>
            <p><strong>کاربران جدید:</strong> $created_users</p>
            <p><strong>کاربران به‌روزرسانی شده:</strong> $updated_users</p>
          </div>";
    
    // Display login credentials
    echo "<div class='seeder-result info'>
            <h4><i class='fas fa-info-circle'></i> کاربران ایجاد شدند</h4>
            <p>کاربران مدیر و پشتیبان با موفقیت ایجاد شدند.</p>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='seeder-result error'>
            <h4><i class='fas fa-times-circle'></i> خطا</h4>
            <p>" . $e->getMessage() . "</p>
          </div>";
}

echo "<div class='text-center mt-4'>
        <a href='login.php' class='btn btn-primary'>ورود به سیستم</a>
        <a href='dashboard.php' class='btn btn-secondary'>داشبورد</a>
      </div>
</div>
</body>
</html>";
?>
