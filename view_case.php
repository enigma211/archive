<?php
/**
 * View Case Details
 * Displays detailed information about a specific case
 */

require_once 'includes/Auth.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/JalaliDate.php';
require_once 'includes/DeadlineHelper.php';

// Initialize Auth and check login
$auth = new Auth();
$auth->requireLogin();

// Get case ID from URL
$case_id = $_GET['case_id'] ?? '';

if (empty($case_id) || !is_numeric($case_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه پرونده نامعتبر است'));
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

$case = null;
$individual = null;
$case_entries = [];
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
        // Get case details with individual information
        $query = "SELECT c.*, i.first_name, i.last_name, i.national_id, i.mobile_number 
                  FROM cases c 
                  JOIN individuals i ON c.individual_id = i.id 
                  WHERE c.id = :case_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get case entries
            $query = "SELECT ce.*, u.username, u.display_name 
                      FROM case_entries ce 
                      JOIN users u ON ce.user_id = u.id 
                      WHERE ce.case_id = :case_id 
                      ORDER BY ce.created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':case_id', $case_id);
            $stmt->execute();
            $case_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get attachments for each entry
            foreach ($case_entries as $key => $entry) {
                $query = "SELECT * FROM attachments WHERE entry_id = :entry_id ORDER BY id ASC";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':entry_id', $entry['id']);
                $stmt->execute();
                $case_entries[$key]['attachments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $error_message = 'پرونده مورد نظر یافت نشد';
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
    <title>مشاهده جزئیات پرونده - سیستم مدیریت شکایات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/mobile-responsive.css" rel="stylesheet">
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
        }
        .info-value {
            color: #6c757d;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
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
        h6 {
            font-size: 1rem;
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
        .info-label {
            font-size: 0.9rem;
        }
        .info-value {
            font-size: 0.9rem;
        }
        .attachment-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
                        <i class="fas fa-folder-open me-2"></i>
                        جزئیات پرونده
                    </h2>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        داشبورد
                    </a>
                    <a href="view_individual.php?id=<?php echo $case['individual_id']; ?>" class="btn btn-light">
                        <i class="fas fa-user me-2"></i>
                        مشاهده فرد
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

        <?php if ($error_message && !$case): ?>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>
                    بازگشت به داشبورد
                </a>
            </div>
        <?php elseif ($case): ?>
            <!-- Case Details -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-folder-open me-2"></i>
                        پرونده: <?php echo htmlspecialchars($case['case_title']); ?>
                    </h4>
                    <?php if ($auth->canEditCases()): ?>
                        <a href="edit_case.php?id=<?php echo $case['id']; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit me-1"></i>
                            ویرایش پرونده
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">عنوان پرونده:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['case_title']); ?></div>
                            </div>
                            
                            <?php if ($auth->canEditCases()): ?>
                                <div class="info-item">
                                    <div class="info-label">نوع پرونده:</div>
                                    <div class="info-value">
                                        <form method="POST" action="update_case_type_stage.php" id="caseTypeForm">
                                            <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <select name="case_type" id="caseType" class="form-select" onchange="updateStageOptions()">
                                                        <option value="">انتخاب نوع پرونده</option>
                                                        <option value="اظهارنامه" <?php echo (isset($case['case_type']) && $case['case_type'] === 'اظهارنامه') ? 'selected' : ''; ?>>اظهارنامه</option>
                                                        <option value="دادخواست بدوی" <?php echo (isset($case['case_type']) && $case['case_type'] === 'دادخواست بدوی') ? 'selected' : ''; ?>>دادخواست بدوی</option>
                                                        <option value="اعاده دادرسی" <?php echo (isset($case['case_type']) && $case['case_type'] === 'اعاده دادرسی') ? 'selected' : ''; ?>>اعاده دادرسی</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <select name="case_stage" id="caseStage" class="form-select">
                                                        <option value="">ابتدا نوع پرونده را انتخاب کنید</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save me-1"></i>
                                                ذخیره تغییرات
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (!empty($case['case_type']) || !empty($case['case_stage'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">وضعیت فعلی:</div>
                                        <div class="info-value">
                                            <?php if (!empty($case['case_type'])): ?>
                                                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($case['case_type']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($case['case_stage'])): ?>
                                                <span class="badge bg-success"><?php echo htmlspecialchars($case['case_stage']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">تاریخ ایجاد:</div>
                                <div class="info-value"><?php echo JalaliDate::formatJalaliDate($case['created_at']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">مهلت پاسخ:</div>
                                <div class="info-value">
                                    <?php if (!empty($case['deadline_date'])): ?>
                                        <?php 
                                        $remaining_days = DeadlineHelper::getRemainingDays($case['deadline_date']);
                                        $status_class = DeadlineHelper::getDeadlineStatusClass($case['deadline_date'], $case['status']);
                                        $status_text = DeadlineHelper::getDeadlineStatusText($case['deadline_date'], $case['status']);
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> me-2">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <small class="text-muted">
                                            <?php echo DeadlineHelper::formatDeadlineDate($case['deadline_date']); ?>
                                            <?php if ($remaining_days !== null && $case['status'] !== 'closed' && $remaining_days > 0): ?>
                                                (<?php echo $remaining_days . ' روز باقی‌مانده'; ?>)
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">بدون مهلت</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($auth->canEditCases()): ?>
                                <div class="info-item">
                                    <div class="info-label">تنظیم/تغییر مهلت:</div>
                                    <div class="info-value">
                                        <form method="POST" action="update_case_deadline.php" class="mt-2">
                                            <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-auto">
                                                    <label class="form-label mb-1">تاریخ جلالی (YYYY/MM/DD)</label>
                                                    <input type="text" class="form-control" name="deadline_jalali" placeholder="مثال: 1404/07/01" value="<?php echo !empty($case['deadline_date']) ? JalaliDate::formatJalaliDate($case['deadline_date']) : ''; ?>">
                                                    <div class="form-text">برای حذف مهلت، این فیلد را خالی بگذارید</div>
                                                </div>
                                                <div class="col-auto">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-save me-1"></i>ثبت مهلت
                                                    </button>
                                                </div>
                                                <?php if (!empty($case['deadline_date'])): ?>
                                                <div class="col-auto">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('form').querySelector('[name=deadline_jalali]').value=''; this.closest('form').submit();">
                                                        <i class="fas fa-times me-1"></i>حذف مهلت
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">شاکی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">کد ملی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['national_id']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">شماره تماس:</div>
                                <div class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($case['mobile_number']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($case['mobile_number']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Case Entries -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            ورودی‌های پرونده (<?php echo count($case_entries); ?> ورودی)
                        </h5>
                        <a href="view_case.php?case_id=<?php echo $case['id']; ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>
                            افزودن ورودی
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($case_entries)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                            <p>هیچ ورودی‌ای برای این پرونده ثبت نشده است</p>
                            <a href="view_case.php?case_id=<?php echo $case['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                افزودن اولین ورودی
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($case_entries as $index => $entry): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                            <?php echo htmlspecialchars($entry['entry_title']); ?>
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">
                                                <?php echo JalaliDate::formatJalaliDate($entry['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($entry['description'])); ?></p>
                                            
                                            <!-- Attachments -->
                                            <?php if (!empty($entry['attachments'])): ?>
                                                <div class="mt-3">
                                                    <h6 class="text-muted mb-2">
                                                        <i class="fas fa-paperclip me-1"></i>
                                                        پیوست‌ها:
                                                    </h6>
                                                    <div class="list-group list-group-flush">
                                                        <?php foreach ($entry['attachments'] as $attachment): ?>
                                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                                <div class="d-flex align-items-center">
                                                                    <a href="download.php?id=<?php echo $attachment['id']; ?>" 
                                                                       class="text-decoration-none d-flex align-items-center">
                                                                        <i class="fas fa-file me-2"></i>
                                                                        <?php echo htmlspecialchars($attachment['attachment_title']); ?>
                                                                    </a>
                                                                    <small class="text-muted ms-3">
                                                                        <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                                                    </small>
                                                                </div>
                                                                <?php if (canDeleteAttachments()): ?>
                                                                    <a href="delete_attachment.php?id=<?php echo $attachment['id']; ?>" 
                                                                       class="btn btn-outline-danger btn-sm"
                                                                       onclick="return confirm('آیا مطمئن هستید که می‌خواهید این پیوست را حذف کنید؟')"
                                                                       title="حذف پیوست">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">
                                                <div><strong>ثبت کننده:</strong> <?php echo htmlspecialchars(!empty($entry['display_name']) ? $entry['display_name'] : $entry['username']); ?></div>
                                                <div><strong>تاریخ:</strong> <?php echo JalaliDate::formatJalaliDate($entry['created_at']); ?></div>
                                            </small>
                                            <?php if ($auth->canEditCaseEntries()): ?>
                                                <div class="mt-2">
                                                    <a href="edit_case_entry.php?id=<?php echo $entry['id']; ?>" 
                                                       class="btn btn-warning btn-sm me-2">
                                                        <i class="fas fa-edit me-1"></i>
                                                        ویرایش ورودی
                                                    </a>
                                                    <?php if ($auth->isAdmin()): ?>
                                                    <a href="delete_case_entry.php?id=<?php echo $entry['id']; ?>&return_url=<?php echo urlencode('view_case.php?case_id=' . $case['id']); ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('آیا مطمئن هستید که می‌خواهید این ورودی را به طور کامل حذف کنید؟ این عمل غیرقابل بازگشت است و تمام پیوست‌های مربوطه نیز حذف خواهند شد.')"
                                                       title="حذف کامل ورودی">
                                                        <i class="fas fa-trash me-1"></i>
                                                        حذف ورودی
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add New Entry Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        افزودن ورودی جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="add_entry.php" enctype="multipart/form-data">
                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="entry_title" class="form-label">عنوان ورودی *</label>
                            <input type="text" class="form-control" id="entry_title" name="entry_title" 
                                   placeholder="عنوان ورودی را وارد کنید" required>
                            <div class="form-text">عنوان این ورودی را مشخص کنید</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="توضیحات ورودی را وارد کنید..." required></textarea>
                            <div class="form-text">توضیحات کامل ورودی را وارد کنید</div>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                               ذخیره
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Actions -->
        <?php endif; ?>
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
    
    <script>
        // Case type and stage management
        const stageOptions = {
            'اظهارنامه': ['جاری', 'پاسخ تهیه شد'],
            'دادخواست بدوی': ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه'],
            'اعاده دادرسی': ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه']
        };
        
        function updateStageOptions() {
            const caseType = document.getElementById('caseType').value;
            const caseStage = document.getElementById('caseStage');
            const currentStage = caseStage.value;
            
            // Clear existing options
            caseStage.innerHTML = '';
            
            if (caseType === '') {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'ابتدا نوع پرونده را انتخاب کنید';
                caseStage.appendChild(option);
                caseStage.disabled = true;
            } else {
                caseStage.disabled = false;
                
                // Add empty option
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'انتخاب مرحله';
                caseStage.appendChild(emptyOption);
                
                // Add stage options based on case type
                if (stageOptions[caseType]) {
                    stageOptions[caseType].forEach(stage => {
                        const option = document.createElement('option');
                        option.value = stage;
                        option.textContent = stage;
                        if (stage === currentStage) {
                            option.selected = true;
                        }
                        caseStage.appendChild(option);
                    });
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const caseType = document.getElementById('caseType');
            const caseStage = document.getElementById('caseStage');
            
            if (caseType && caseStage) {
                // Store current stage value
                const currentStage = '<?php echo isset($case['case_stage']) ? addslashes($case['case_stage']) : ''; ?>';
                
                // Update stage options based on selected type
                if (caseType.value) {
                    updateStageOptions();
                    
                    // Set the current stage if it exists
                    if (currentStage) {
                        caseStage.value = currentStage;
                    }
                }
            }
        });
    </script>
</body>
</html>
