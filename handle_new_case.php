<?php
/**
 * Handle New Case Creation - Step 1
 * Shows detailed form for creating case with first entry and attachments
 */

require_once 'includes/SessionHelper.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';

// Start session
SessionHelper::start();

// Check if user is logged in
if (!SessionHelper::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?error=invalid_request');
    exit();
}

// Get form data
$individual_id = $_POST['individual_id'] ?? '';
$case_title = trim($_POST['case_title'] ?? '');
$complaint_date = $_POST['complaint_date'] ?? '';

// Server-side validation
$errors = [];

if (empty($individual_id) || !is_numeric($individual_id)) {
    $errors[] = 'شناسه فرد نامعتبر است';
}

if (empty($case_title)) {
    $errors[] = 'عنوان پرونده اجباری است';
} elseif (strlen($case_title) < 3) {
    $errors[] = 'عنوان پرونده باید حداقل 3 کاراکتر باشد';
}

if (empty($complaint_date)) {
    $errors[] = 'تاریخ درج شکایت اجباری است';
} else {
    // Validate Jalali date format (YYYY/MM/DD)
    if (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $complaint_date)) {
        $errors[] = 'تاریخ درج شکایت باید به فرمت جلالی (1404/06/26) باشد';
    } else {
        $date_parts = explode('/', $complaint_date);
        $year = (int)$date_parts[0];
        $month = (int)$date_parts[1];
        $day = (int)$date_parts[2];
        
        // Basic validation for Jalali date
        if ($year < 1300 || $year > 1500 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            $errors[] = 'تاریخ درج شکایت نامعتبر است';
        }
    }
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: view_individual.php?id=' . $individual_id . '&error=' . urlencode($error_message));
    exit();
}

// Connect to database and get individual info
$database = new Database();
$conn = $database->getConnection();

$individual = null;
if ($conn) {
    try {
        $query = "SELECT * FROM individuals WHERE id = :individual_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':individual_id', $individual_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $individual = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            header('Location: list_individuals.php?error=' . urlencode('فرد مورد نظر یافت نشد'));
            exit();
        }
    } catch (Exception $e) {
        header('Location: dashboard.php?error=' . urlencode('خطا در بارگذاری اطلاعات فرد'));
        exit();
    }
} else {
    header('Location: dashboard.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

// Get current user data
$user = SessionHelper::getCurrentUser();

// Handle error messages from URL parameters
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد پرونده جدید - سیستم مدیریت شکایات</title>
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
            margin-bottom: 1.5rem;
        }
        .header h2 {
            font-size: 1.4rem;
        }
        .header p {
            font-size: 0.9rem;
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
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-label {
            font-size: 0.9rem;
        }
        .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .info-item strong {
            font-size: 0.9rem;
        }
        .info-item span {
            font-size: 0.9rem;
        }
        .attachment-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .remove-attachment {
            color: #dc3545;
            cursor: pointer;
        }
        .remove-attachment:hover {
            color: #a71e2a;
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
                        <i class="fas fa-folder-plus me-2"></i>
                        ایجاد پرونده جدید
                    </h2>
                    <p class="mb-0">مرحله 2: ثبت دادخواست اولیه</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        داشبورد
                    </a>
                    <a href="view_individual.php?id=<?php echo $individual['id']; ?>" class="btn btn-light">
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

        <!-- Individual Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    اطلاعات کامل شاکی
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">نام و نام خانوادگی:</strong><br>
                            <span class="text-dark"><?php echo htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">کد ملی:</strong><br>
                            <span class="text-dark"><?php echo htmlspecialchars($individual['national_id']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">شماره موبایل:</strong><br>
                            <?php if (!empty($individual['mobile_number'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($individual['mobile_number']); ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars($individual['mobile_number']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">ثبت نشده</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">نام پدر:</strong><br>
                            <span class="text-dark"><?php echo !empty($individual['father_name']) ? htmlspecialchars($individual['father_name']) : 'ثبت نشده'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">رشته دانشگاهی:</strong><br>
                            <span class="text-dark"><?php echo !empty($individual['university_major']) ? htmlspecialchars($individual['university_major']) : 'ثبت نشده'; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">نام آزمون:</strong><br>
                            <span class="text-dark"><?php echo !empty($individual['exam_name']) ? htmlspecialchars($individual['exam_name']) : 'ثبت نشده'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong class="text-primary">تاریخ ثبت در سیستم:</strong><br>
                            <span class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo JalaliDate::formatJalaliDate($individual['created_at']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="row">
                    <div class="col-12">
                        <div class="info-item">
                            <strong class="text-primary">عنوان پرونده جدید:</strong><br>
                            <span class="badge bg-warning fs-6">
                                <i class="fas fa-folder-plus me-1"></i>
                                <?php echo htmlspecialchars($case_title); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Case Entry Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    ثبت دادخواست اولیه
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="save_full_case.php" enctype="multipart/form-data">
                    <input type="hidden" name="individual_id" value="<?php echo $individual['id']; ?>">
                    <input type="hidden" name="case_title" value="<?php echo htmlspecialchars($case_title); ?>">
                    <input type="hidden" name="complaint_date" value="<?php echo htmlspecialchars($complaint_date); ?>">
                    
                    <div class="mb-3">
                        <label for="entry_title" class="form-label">عنوان دادخواست *</label>
                        <input type="text" class="form-control" id="entry_title" name="entry_title" 
                               value="ثبت دادخواست اولیه" required>
                        <div class="form-text">عنوان این ورودی را مشخص کنید</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">توضیحات دادخواست *</label>
                        <textarea class="form-control" id="description" name="description" rows="6" 
                                  placeholder="جزئیات دادخواست را شرح دهید..." required></textarea>
                        <div class="form-text">توضیحات کامل دادخواست را وارد کنید</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deadline_days" class="form-label">مهلت پاسخ (روز) *</label>
                                <input type="number" class="form-control" id="deadline_days" name="deadline_days" 
                                       min="1" placeholder="مثال: 90" required>
                                <div class="form-text">تعداد روزهای مهلت برای پاسخ دادن (می‌توانید هر عددی را وارد کنید)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">اطلاعات مهلت</label>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>
                                        مهلت پاسخ از تاریخ ایجاد پرونده محاسبه می‌شود و در صورت عدم پاسخ، 
                                        وضعیت پرونده به حالت فوری تغییر می‌کند.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Attachments Section -->
                    <div class="mb-4">
                        <label class="form-label">پیوست‌ها</label>
                        <div id="attachments-container">
                            <div class="attachment-item">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">عنوان پیوست</label>
                                            <input type="text" class="form-control" name="attachment_titles[]" 
                                                   placeholder="عنوان پیوست را وارد کنید">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">فایل</label>
                                            <input type="file" class="form-control" name="attachments[]" 
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary" id="add-attachment">
                            <i class="fas fa-plus me-2"></i>
                            افزودن پیوست دیگر
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view_individual.php?id=<?php echo $individual['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            انصراف
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            ایجاد پرونده و ثبت دادخواست
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic file attachment functionality
        document.getElementById('add-attachment').addEventListener('click', function() {
            const container = document.getElementById('attachments-container');
            const newAttachment = document.createElement('div');
            newAttachment.className = 'attachment-item';
            newAttachment.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">عنوان پیوست</label>
                            <input type="text" class="form-control" name="attachment_titles[]" 
                                   placeholder="عنوان پیوست را وارد کنید">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">فایل</label>
                            <input type="file" class="form-control" name="attachments[]" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-danger remove-attachment-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(newAttachment);
            
            // Add remove functionality
            newAttachment.querySelector('.remove-attachment-btn').addEventListener('click', function() {
                newAttachment.remove();
            });
        });
        
        // Add remove functionality to existing attachment items
        document.querySelectorAll('.remove-attachment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.attachment-item').remove();
            });
        });
    </script>
</body>
</html>
