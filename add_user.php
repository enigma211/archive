<?php
/**
 * Add New User (Admin Only)
 * Allows admins to create new user accounts
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

// Handle messages from URL parameters
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن کاربر جدید - سیستم مدیریت شکایات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background-color: #f8f9fa;
            font-size: 0.9rem;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        h2 {
            font-size: 1.4rem;
        }
        h4 {
            font-size: 1.2rem;
        }
        h5 {
            font-size: 1.1rem;
        }
        .btn {
            font-size: 0.85rem;
        }
        .form-control {
            font-size: 0.9rem;
        }
        .form-label {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        افزودن کاربر جدید
                    </h2>
                    <p class="mb-0">ایجاد حساب کاربری جدید</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        داشبورد
                    </a>
                    <a href="manage_users.php" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>
                        بازگشت
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    اطلاعات کاربر جدید
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="create_user.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">نام کاربری *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="نام کاربری را وارد کنید" required>
                        <div class="form-text">نام کاربری باید منحصر به فرد باشد</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">نام نمایشی</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" 
                               placeholder="نام نمایشی را وارد کنید (اختیاری)">
                        <div class="form-text">نام نمایشی برای شناسایی آسان‌تر کاربر (مثل: شاکری)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">رمز عبور *</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="رمز عبور را وارد کنید" required minlength="6">
                        <div class="form-text">رمز عبور باید حداقل 6 کاراکتر باشد</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">تأیید رمز عبور *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="رمز عبور را دوباره وارد کنید" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">نقش کاربر *</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">نقش کاربر را انتخاب کنید</option>
                            <option value="support">پشتیبان</option>
                            <option value="admin">مدیر</option>
                        </select>
                        <div class="form-text">نقش کاربر را با دقت انتخاب کنید</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_users.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            انصراف
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            ایجاد کاربر
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('رمز عبور و تأیید آن باید یکسان باشند');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>
