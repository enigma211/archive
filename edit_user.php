<?php
/**
 * Edit User (Admin Only)
 * Allows admins to edit user passwords and roles
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

// Handle messages from URL parameters
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Connect to database
$conn = getConnection();

$user = null;
if ($conn) {
    try {
        $query = "SELECT id, username, display_name, role, created_at FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            header('Location: manage_users.php?error=' . urlencode('کاربر مورد نظر یافت نشد'));
            exit();
        }
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری اطلاعات کاربر: " . $e->getMessage();
    }
} else {
    $error_message = "خطا در اتصال به پایگاه داده";
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش کاربر - سیستم مدیریت شکایات</title>
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
                        <i class="fas fa-user-edit me-2"></i>
                        ویرایش کاربر
                    </h2>
                    <p class="mb-0">ویرایش اطلاعات کاربر</p>
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

        <?php if ($user): ?>
            <!-- User Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        اطلاعات کاربر
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>نام کاربری:</strong> <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>نقش:</strong> 
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger">مدیر</span>
                            <?php else: ?>
                                <span class="badge bg-info">پشتیبان</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>تاریخ ایجاد:</strong> <?php echo date('Y/m/d H:i', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        ویرایش اطلاعات
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="update_user.php">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="display_name" class="form-label">نام نمایشی</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" 
                                   value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>"
                                   placeholder="نام نمایشی را وارد کنید (اختیاری)">
                            <div class="form-text">نام نمایشی برای شناسایی آسان‌تر کاربر (مثل: شاکری)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">رمز عبور جدید</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="رمز عبور جدید را وارد کنید">
                            <div class="form-text">اگر می‌خواهید رمز عبور را تغییر دهید، فیلد بالا را پر کنید</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">تأیید رمز عبور</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="رمز عبور را دوباره وارد کنید">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">نقش کاربر</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="support" <?php echo $user['role'] === 'support' ? 'selected' : ''; ?>>پشتیبان</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدیر</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="manage_users.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                انصراف
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('رمز عبور و تأیید آن باید یکسان باشند');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>
