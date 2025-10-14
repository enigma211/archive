<?php
/**
 * Edit Individual Page
 * Allows editing of individual information for both admin and support users
 */

require_once 'includes/Auth.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditIndividuals();

// Get current user data
$user = $auth->getCurrentUser();
$username = $user['username'];
$user_role = $user['role'];

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
$error_message = '';
$success_message = '';

// Handle messages from URL parameters
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $university_major = trim($_POST['university_major'] ?? '');
    $exam_name = trim($_POST['exam_name'] ?? '');

    // Server-side validation
    $errors = [];

    if (empty($first_name)) {
        $errors[] = 'نام اجباری است';
    }

    if (empty($last_name)) {
        $errors[] = 'نام خانوادگی اجباری است';
    }

    if (empty($national_id)) {
        $errors[] = 'کد ملی اجباری است';
    } elseif (!preg_match('/^[0-9]{10}$/', $national_id)) {
        $errors[] = 'کد ملی باید 10 رقم باشد';
    }

    if (!empty($mobile_number) && !preg_match('/^09[0-9]{9}$/', $mobile_number)) {
        $errors[] = 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد';
    }

    if (empty($errors)) {
        try {
            // Check if national ID already exists for another individual
            $query = "SELECT id FROM individuals WHERE national_id = :national_id AND id != :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':national_id', $national_id);
            $stmt->bindParam(':id', $individual_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_message = 'کد ملی تکراری است';
            } else {
                // Update individual information
                $query = "UPDATE individuals SET 
                         first_name = :first_name, 
                         last_name = :last_name, 
                         national_id = :national_id, 
                         mobile_number = :mobile_number, 
                         father_name = :father_name, 
                         university_major = :university_major, 
                         exam_name = :exam_name 
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':national_id', $national_id);
                $stmt->bindParam(':mobile_number', $mobile_number);
                $stmt->bindParam(':father_name', $father_name);
                $stmt->bindParam(':university_major', $university_major);
                $stmt->bindParam(':exam_name', $exam_name);
                $stmt->bindParam(':id', $individual_id);
                
                if ($stmt->execute()) {
                    $success_message = 'اطلاعات فرد با موفقیت به‌روزرسانی شد';
                } else {
                    $error_message = 'خطا در به‌روزرسانی اطلاعات';
                }
            }
        } catch (Exception $e) {
            error_log("Edit individual error: " . $e->getMessage());
            $error_message = 'خطا در ذخیره اطلاعات';
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}

// Get individual details
if ($conn) {
    try {
        $query = "SELECT * FROM individuals WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $individual_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $individual = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = 'فرد مورد نظر یافت نشد';
        }
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری اطلاعات: " . $e->getMessage();
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
    <title>ویرایش اطلاعات فرد - سیستم مدیریت شکایات</title>
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
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .header h2 {
            font-size: 1.4rem;
        }
        .header p {
            font-size: 0.9rem;
        }
        .form-control {
            font-size: 0.9rem;
        }
        .form-label {
            font-size: 0.9rem;
        }
        .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
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
                        ویرایش اطلاعات فرد
                    </h2>
                    <p class="mb-0 mt-1">ویرایش اطلاعات: <?php echo $individual ? htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']) : ''; ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        داشبورد
                    </a>
                    <a href="view_individual.php?id=<?php echo $individual_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>
                        بازگشت به جزئیات فرد
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
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        ویرایش اطلاعات فرد: <?php echo htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">نام *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($individual['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">نام خانوادگی *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($individual['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="national_id" class="form-label">کد ملی *</label>
                                    <input type="text" class="form-control" id="national_id" name="national_id" 
                                           value="<?php echo htmlspecialchars($individual['national_id']); ?>" 
                                           pattern="[0-9]{10}" maxlength="10" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mobile_number" class="form-label">شماره موبایل</label>
                                    <input type="text" class="form-control" id="mobile_number" name="mobile_number" 
                                           value="<?php echo htmlspecialchars($individual['mobile_number']); ?>" 
                                           pattern="09[0-9]{9}" maxlength="11">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="father_name" class="form-label">نام پدر</label>
                                    <input type="text" class="form-control" id="father_name" name="father_name" 
                                           value="<?php echo htmlspecialchars($individual['father_name']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="university_major" class="form-label">رشته دانشگاهی</label>
                                    <input type="text" class="form-control" id="university_major" name="university_major" 
                                           value="<?php echo htmlspecialchars($individual['university_major']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="exam_name" class="form-label">نام آزمون</label>
                                    <input type="text" class="form-control" id="exam_name" name="exam_name" 
                                           value="<?php echo htmlspecialchars($individual['exam_name']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        ذخیره تغییرات
                                    </button>
                                    <a href="view_individual.php?id=<?php echo $individual_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        انصراف
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Individual Info Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        اطلاعات تکمیلی
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>تاریخ ثبت:</strong> <?php echo JalaliDate::formatJalaliDate($individual['created_at']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>شناسه فرد:</strong> <?php echo $individual['id']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
