<?php
/**
 * User Management Page (Admin Only)
 * Allows admins to view and manage system users
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

        // Get current user data
$current_user_id = getCurrentUserId();
$current_username = getCurrentUsername();

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

$users = [];
if ($conn) {
    try {
        $query = "SELECT id, username, display_name, role, created_at, failed_login_attempts, locked_until, last_failed_login 
                  FROM users ORDER BY created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری لیست کاربران: " . $e->getMessage();
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
    <title>مدیریت کاربران - سیستم مدیریت شکایات</title>
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
        .btn-danger {
            border-radius: 8px;
        }
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.7rem;
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
        .table {
            font-size: 0.9rem;
        }
        .admin-badge {
            background-color: #dc3545;
        }
        .support-badge {
            background-color: #0dcaf0;
            color: #000;
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
                        <i class="fas fa-users-cog me-2"></i>
                        مدیریت کاربران
                    </h2>
                    <p class="mb-0">مدیریت کاربران سیستم</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>
                        بازگشت به داشبورد
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

        <!-- Users List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    لیست کاربران (<?php echo count($users); ?> کاربر)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>هیچ کاربری در سیستم ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>نام کاربری / نام نمایشی</th>
                                    <th>نقش</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if (isset($user['display_name']) && !empty($user['display_name']) && $user['display_name'] !== $user['username']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($user['display_name']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($user['id'] == $current_user_id): ?>
                                                <span class="badge bg-success ms-2">شما</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge admin-badge role-badge">
                                                    <i class="fas fa-crown me-1"></i>
                                                    مدیر
                                                </span>
                                            <?php else: ?>
                                                <span class="badge support-badge role-badge">
                                                    <i class="fas fa-headset me-1"></i>
                                                    پشتیبان
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-lock me-1"></i>
                                                    قفل شده
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    تا: <?php echo date('Y/m/d H:i', strtotime($user['locked_until'])); ?>
                                                </small>
                                            <?php elseif ($user['failed_login_attempts'] > 0): ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    <?php echo $user['failed_login_attempts']; ?> تلاش ناموفق
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    فعال
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y/m/d H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit me-1"></i>
                                                    ویرایش
                                                </a>
                                                <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
                                                    <a href="unlock_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-warning btn-sm"
                                                       onclick="return confirm('آیا مطمئن هستید که می‌خواهید این کاربر را باز کنید؟')">
                                                        <i class="fas fa-unlock me-1"></i>
                                                        باز کردن
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (canDeleteUser($user['id'])): ?>
                                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟')">
                                                        <i class="fas fa-trash me-1"></i>
                                                        حذف
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center mt-4">
            <a href="add_user.php" class="btn btn-success">
                <i class="fas fa-user-plus me-2"></i>
                افزودن کاربر جدید
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
