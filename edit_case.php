<?php
/**
 * Edit Case Information
 * Allows editing case details including deadline
 */

require_once 'includes/Auth.php';
require_once 'config.php';
require_once 'includes/JalaliDate.php';
require_once 'includes/DeadlineHelper.php';
require_once 'includes/AuditLogger.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditCases();

// Get case ID from URL
$case_id = $_GET['id'] ?? '';

if (empty($case_id) || !is_numeric($case_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه پرونده نامعتبر است'));
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

$case = null;
$individual = null;
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_title = trim($_POST['case_title'] ?? '');
    $case_type = $_POST['case_type'] ?? '';
    $case_stage = $_POST['case_stage'] ?? '';
    $deadline_days = $_POST['deadline_days'] ?? '';
    
    // Validation
    if (empty($case_title)) {
        $error_message = 'عنوان پرونده الزامی است';
    } elseif (!empty($deadline_days) && (!is_numeric($deadline_days) || $deadline_days < 0)) {
        $error_message = 'مهلت پاسخ باید یک عدد صفر یا مثبت باشد';
    } else {
        try {
            $conn->beginTransaction();
            
            // Get current case data for change tracking and deadline calculation
            $query = "SELECT case_title, case_type, case_stage, deadline_days, deadline_date, created_at FROM cases WHERE id = :case_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':case_id', $case_id);
            $stmt->execute();
            $current_case = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate deadline date if deadline_days is provided and greater than 0
            $deadline_date = null;
            if (!empty($deadline_days) && $deadline_days > 0 && $current_case) {
                $deadline_date = DeadlineHelper::calculateDeadlineDate($current_case['created_at'], $deadline_days);
            }
            
            // Update case
            $query = "UPDATE cases SET case_title = :case_title, case_type = :case_type, case_stage = :case_stage,
                      deadline_days = :deadline_days, deadline_date = :deadline_date 
                      WHERE id = :case_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':case_title', $case_title);
            
            // Handle case_type and case_stage
            $case_type_param = !empty($case_type) ? $case_type : null;
            $case_stage_param = !empty($case_stage) ? $case_stage : null;
            $stmt->bindParam(':case_type', $case_type_param);
            $stmt->bindParam(':case_stage', $case_stage_param);
            
            // Handle deadline_days parameter properly (allow 0 or null for no deadline)
            $deadline_days_param = (!empty($deadline_days) || $deadline_days === '0') ? (int)$deadline_days : null;
            if ($deadline_days_param === 0) {
                $deadline_days_param = null;
            }
            $stmt->bindParam(':deadline_days', $deadline_days_param, PDO::PARAM_INT);
            $stmt->bindParam(':deadline_date', $deadline_date);
            $stmt->bindParam(':case_id', $case_id);
            
            if ($stmt->execute()) {
                $conn->commit();
                // Audit: log changes
                $new_values = [
                    'case_title' => $case_title,
                    'case_type' => $case_type_param,
                    'case_stage' => $case_stage_param,
                    'deadline_days' => $deadline_days_param,
                    'deadline_date' => $deadline_date
                ];
                $old_values = [
                    'case_title' => $current_case['case_title'] ?? null,
                    'case_type' => $current_case['case_type'] ?? null,
                    'case_stage' => $current_case['case_stage'] ?? null,
                    'deadline_days' => isset($current_case['deadline_days']) ? (int)$current_case['deadline_days'] : null,
                    'deadline_date' => $current_case['deadline_date'] ?? null
                ];
                $changes = [];
                foreach ($new_values as $k=>$v) {
                    $ov = $old_values[$k] ?? null;
                    // normalize empty strings to null for comparison
                    if ($v === '') $v = null;
                    if ($ov === '') $ov = null;
                    if ($ov != $v) {
                        $changes[$k] = ['old' => $ov, 'new' => $v];
                    }
                }
                AuditLogger::log('case_update', 'case', $case_id, [ 'changes' => $changes ]);
                header('Location: cases.php?success=' . urlencode('اطلاعات پرونده با موفقیت به‌روزرسانی شد'));
                exit();
            } else {
                $conn->rollBack();
                $error_message = 'خطا در به‌روزرسانی اطلاعات پرونده';
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "خطا در به‌روزرسانی: " . $e->getMessage();
        }
    }
}

// Get case details
if ($conn) {
    try {
        $query = "SELECT c.id, c.case_title, c.status, c.created_at, c.deadline_date, c.deadline_days,
                  c.case_type, c.case_stage, c.individual_id, i.first_name, i.last_name, i.national_id 
                  FROM cases c 
                  JOIN individuals i ON c.individual_id = i.id 
                  WHERE c.id = :case_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>ویرایش پرونده - سیستم مدیریت شکایات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background-color: #f8f9fa;
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .info-item {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-right: 4px solid #667eea;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #6c757d;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        ویرایش پرونده
                    </h4>
                    <div class="text-end">
                        <small>کاربر: <?php echo htmlspecialchars($username); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message && !$case): ?>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-2"></i>
                    بازگشت به داشبورد
                </a>
            </div>
        <?php elseif ($case): ?>
            <!-- Case Information Display -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        اطلاعات پرونده
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">شاکی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">کد ملی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['national_id']); ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">تاریخ ایجاد:</div>
                                <div class="info-value"><?php echo JalaliDate::formatJalaliDate($case['created_at']); ?></div>
                            </div>
                            
                            <?php if (!empty($case['deadline_date'])): ?>
                                <div class="info-item">
                                    <div class="info-label">مهلت فعلی:</div>
                                    <div class="info-value">
                                        <?php 
                                        $remaining_days = DeadlineHelper::getRemainingDays($case['deadline_date']);
                                        $status_class = DeadlineHelper::getDeadlineStatusClass($case['deadline_date']);
                                        $status_text = DeadlineHelper::getDeadlineStatusText($case['deadline_date']);
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> me-2">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <small class="text-muted">
                                            <?php echo DeadlineHelper::formatDeadlineDate($case['deadline_date']); ?>
                                            <?php if ($remaining_days !== null && $remaining_days > 0): ?>
                                                (<?php echo $remaining_days . ' روز باقی‌مانده'; ?>)
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
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
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="case_title" class="form-label">عنوان پرونده *</label>
                                    <input type="text" class="form-control" id="case_title" name="case_title" 
                                           value="<?php echo htmlspecialchars($case['case_title']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="case_type" class="form-label">نوع پرونده</label>
                                    <select class="form-select" id="case_type" name="case_type" onchange="updateStageOptionsEdit()">
                                        <option value="">انتخاب نوع پرونده</option>
                                        <option value="اظهارنامه" <?php echo (isset($case['case_type']) && $case['case_type'] === 'اظهارنامه') ? 'selected' : ''; ?>>اظهارنامه</option>
                                        <option value="دادخواست بدوی" <?php echo (isset($case['case_type']) && $case['case_type'] === 'دادخواست بدوی') ? 'selected' : ''; ?>>دادخواست بدوی</option>
                                        <option value="اعاده دادرسی" <?php echo (isset($case['case_type']) && $case['case_type'] === 'اعاده دادرسی') ? 'selected' : ''; ?>>اعاده دادرسی</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="case_stage" class="form-label">مرحله پرونده</label>
                                    <select class="form-select" id="case_stage" name="case_stage">
                                        <option value="">ابتدا نوع پرونده را انتخاب کنید</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deadline_days" class="form-label">مهلت پاسخ (روز)</label>
                                    <input type="number" class="form-control" id="deadline_days" name="deadline_days" 
                                           value="<?php echo htmlspecialchars($case['deadline_days'] ?? ''); ?>" 
                                           min="0" placeholder="مثال: 90 (برای بدون مهلت خالی بگذارید)">
                                    <div class="form-text">تعداد روزهای مهلت برای پاسخ دادن (اختیاری - برای بدون مهلت، خالی بگذارید یا 0 وارد کنید)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="cases.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                بازگشت به لیست
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
        // Case type and stage management
        const stageOptions = {
            'اظهارنامه': ['جاری', 'پاسخ تهیه شد'],
            'دادخواست بدوی': ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه'],
            'اعاده دادرسی': ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه']
        };
        
        function updateStageOptionsEdit() {
            const caseType = document.getElementById('case_type').value;
            const caseStage = document.getElementById('case_stage');
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
            const caseType = document.getElementById('case_type');
            const caseStage = document.getElementById('case_stage');
            
            if (caseType && caseStage) {
                // Store current stage value
                const currentStage = '<?php echo isset($case['case_stage']) ? addslashes($case['case_stage']) : ''; ?>';
                
                // Update stage options based on selected type
                if (caseType.value) {
                    updateStageOptionsEdit();
                    
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
