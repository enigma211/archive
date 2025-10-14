<?php
/**
 * View Individual Details
 * Displays detailed information about a specific individual
 */

require_once 'includes/Auth.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';

// Initialize Auth and check login
$auth = new Auth();
$auth->requireLogin();

// Get individual ID from URL
$individual_id = $_GET['id'] ?? '';

if (empty($individual_id) || !is_numeric($individual_id)) {
    header('Location: list_individuals.php?error=' . urlencode('شناسه فرد نامعتبر است'));
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

$individual = null;
$cases = [];
$error_message = '';
$success_message = '';

// Handle messages from URL parameters
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

if ($conn) {
    try {
        // Get individual details
        $query = "SELECT * FROM individuals WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $individual_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $individual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get associated cases
            $query = "SELECT * FROM cases WHERE individual_id = :individual_id ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':individual_id', $individual_id);
            $stmt->execute();
            $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = 'فرد مورد نظر یافت نشد';
        }
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری اطلاعات: " . $e->getMessage();
    }
} else {
    $error_message = "خطا در اتصال به پایگاه داده";
}

// Get current user data
$user = $auth->getCurrentUser();
$username = $user['username'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشاهده جزئیات فرد - سیستم مدیریت شکایات</title>
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
            padding: 0.8rem 1.2rem;
        }
        .card-header h4 {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .card-header h5 {
            font-size: 1rem;
            margin-bottom: 0;
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
        .info-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        .info-value {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .header h2 {
            font-size: 1.4rem;
        }
        .header p {
            font-size: 0.9rem;
        }
        .table {
            font-size: 0.9rem;
        }
        .table thead th {
            font-size: 0.85rem;
            padding: 0.6rem 0.75rem;
        }
        .table tbody td {
            font-size: 0.85rem;
            padding: 0.6rem 0.75rem;
        }
        .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
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
                        <i class="fas fa-user me-2"></i>
                        جزئیات فرد
                    </h2>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
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

        <?php if ($error_message && !$individual): ?>
            <div class="text-center">
                <a href="list_individuals.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>
                    بازگشت به لیست افراد
                </a>
            </div>
        <?php elseif ($individual): ?>
            <!-- Individual Details -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            اطلاعات فرد: <?php echo htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']); ?>
                        </h4>
                        <?php if ($auth->canEditIndividuals()): ?>
                            <a href="edit_individual.php?id=<?php echo $individual['id']; ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-edit me-1"></i>
                                ویرایش
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">نام:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['first_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">نام خانوادگی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['last_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">کد ملی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['national_id']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">شماره موبایل:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['mobile_number']); ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">نام پدر:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['father_name'] ?: 'ثبت نشده'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">رشته دانشگاهی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['university_major'] ?: 'ثبت نشده'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">نام آزمون:</div>
                                <div class="info-value"><?php echo htmlspecialchars($individual['exam_name'] ?: 'ثبت نشده'); ?></div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-item">
                                <div class="info-label">تاریخ ثبت:</div>
                                <div class="info-value"><?php echo JalaliDate::formatJalaliDate($individual['created_at']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create New Case Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-plus me-2"></i>
                        ایجاد پرونده جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="handle_new_case.php">
                        <input type="hidden" name="individual_id" value="<?php echo $individual['id']; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="case_title" class="form-label">عنوان پرونده *</label>
                                    <input type="text" class="form-control" id="case_title" name="case_title" 
                                           placeholder="عنوان پرونده را وارد کنید" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="complaint_date" class="form-label">تاریخ درج شکایت *</label>
                                    <input type="text" class="form-control" id="complaint_date" name="complaint_date" 
                                           value="<?php echo JalaliDate::getCurrentJalaliDate(); ?>" required>
                                    <div class="form-text">تاریخ را اینگونه وارد کنید : 1404/07/07</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus me-2"></i>
                                            ایجاد پرونده
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Associated Cases -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-open me-2"></i>
                        پرونده‌های مرتبط (<?php echo count($cases); ?> پرونده)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($cases)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>هیچ پرونده‌ای برای این فرد ثبت نشده است</p>
                            <p>برای شروع، پرونده جدیدی ایجاد کنید</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>عنوان پرونده</th>
                                        <th>وضعیت</th>
                                        <th>تاریخ ایجاد</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cases as $case): ?>
                                        <tr>
                                             <td>
                                                 <div>
                                                     <div class="fw-bold"><?php echo htmlspecialchars($case['case_title']); ?></div>
                                                     <small class="text-muted">شناسه: <?php echo $case['id']; ?></small>
                                                 </div>
                                             </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                switch ($case['status']) {
                                                    case 'open':
                                                        $status_class = 'bg-warning';
                                                        $status_text = 'باز';
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'bg-info';
                                                        $status_text = 'در حال بررسی';
                                                        break;
                                                    case 'closed':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'بسته';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo JalaliDate::formatJalaliDate($case['created_at']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="view_case.php?case_id=<?php echo $case['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>
                                                    مشاهده جزئیات
                                                </a>
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
                <?php if ($auth->canEditIndividuals()): ?>
                    <a href="edit_individual.php?id=<?php echo $individual['id']; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-2"></i>
                        ویرایش اطلاعات فرد
                    </a>
                <?php endif; ?>
                <a href="list_individuals.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>
                    بازگشت به لیست افراد
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // اعتبارسنجی تاریخ جلالی
        document.getElementById('complaint_date').addEventListener('input', function() {
            const dateInput = this.value;
            const datePattern = /^\d{4}\/\d{2}\/\d{2}$/;
            
            if (dateInput && !datePattern.test(dateInput)) {
                this.setCustomValidity('تاریخ باید به فرمت جلالی (1404/06/26) باشد');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // اعتبارسنجی فرم
        document.querySelector('form').addEventListener('submit', function(e) {
            const complaintDate = document.getElementById('complaint_date').value;
            const datePattern = /^\d{4}\/\d{2}\/\d{2}$/;
            
            if (!datePattern.test(complaintDate)) {
                e.preventDefault();
                alert('تاریخ درج شکایت باید به فرمت جلالی (1404/06/26) باشد');
                return false;
            }
        });
    </script>
</body>
</html>
