<?php
/**
 * Edit Case Entry Page
 * Allows editing of case entry information for both admin and support users
 */

require_once 'includes/Auth.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditCaseEntries();

// Get current user data
$user = $auth->getCurrentUser();
$username = $user['username'];
$user_role = $user['role'];

// Get entry ID from URL
$entry_id = $_GET['id'] ?? '';

if (empty($entry_id) || !is_numeric($entry_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه ورودی نامعتبر است'));
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

$entry = null;
$case = null;
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
    $entry_title = trim($_POST['entry_title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Server-side validation
    $errors = [];

    if (empty($entry_title)) {
        $errors[] = 'عنوان ورودی اجباری است';
    }

    if (empty($description)) {
        $errors[] = 'توضیحات ورودی اجباری است';
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Update entry information
            $query = "UPDATE case_entries SET 
                     entry_title = :entry_title, 
                     description = :description 
                     WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':entry_title', $entry_title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $entry_id);
            
            if ($stmt->execute()) {
                // Handle file uploads if any
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    $attachment_titles = $_POST['attachment_titles'] ?? [];
                    
                    // Create monthly upload directory structure
                    require_once 'includes/AttachmentHelper.php';
                    $upload_dir = AttachmentHelper::createMonthlyUploadDir();
                    
                    // Process each uploaded file
                    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                        if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                            $original_filename = $_FILES['attachments']['name'][$i];
                            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                            
                            // Generate unique filename
                            $unique_id = uniqid('', true); // More entropy
                            $unique_filename = $unique_id . '.' . $file_extension;
                            $file_path = $upload_dir . $unique_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $file_path)) {
                                // Get attachment title (use original filename if not provided)
                                $attachment_title = $attachment_titles[$i] ?? pathinfo($original_filename, PATHINFO_FILENAME);
                                
                                // Insert attachment record
                                $query = "INSERT INTO attachments (entry_id, attachment_title, file_path, original_filename) 
                                          VALUES (:entry_id, :attachment_title, :file_path, :original_filename)";
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':entry_id', $entry_id);
                                $stmt->bindParam(':attachment_title', $attachment_title);
                                $stmt->bindParam(':file_path', $file_path);
                                $stmt->bindParam(':original_filename', $original_filename);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("خطا در ثبت اطلاعات پیوست");
                                }
                            } else {
                                throw new Exception("خطا در آپلود فایل: " . $original_filename);
                            }
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                $success_message = 'اطلاعات ورودی با موفقیت به‌روزرسانی شد';
            } else {
                throw new Exception("خطا در به‌روزرسانی اطلاعات");
            }
        } catch (Exception $e) {
            // Rollback transaction
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            error_log("Edit case entry error: " . $e->getMessage());
            $error_message = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}

// Get entry details with case information and attachments
if ($conn) {
    try {
        $query = "SELECT ce.*, c.case_title, c.id as case_id, c.individual_id, i.first_name, i.last_name 
                  FROM case_entries ce 
                  JOIN cases c ON ce.case_id = c.id 
                  JOIN individuals i ON c.individual_id = i.id 
                  WHERE ce.id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $entry_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            $case = [
                'id' => $entry['case_id'],
                'case_title' => $entry['case_title'],
                'individual_id' => $entry['individual_id'],
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name']
            ];
            
            // Get attachments for this entry
            $query = "SELECT * FROM attachments WHERE entry_id = :entry_id ORDER BY id ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':entry_id', $entry_id);
            $stmt->execute();
            $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = 'ورودی مورد نظر یافت نشد';
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
    <title>ویرایش ورودی پرونده - سیستم مدیریت شکایات</title>
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
                        <i class="fas fa-edit me-2"></i>
                        ویرایش ورودی پرونده
                    </h2>
                    <p class="mb-0 mt-1">ویرایش ورودی: <?php echo $entry ? htmlspecialchars($entry['entry_title']) : ''; ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        داشبورد
                    </a>
                    <a href="view_case.php?case_id=<?php echo $case['id']; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-right me-2"></i>
                        بازگشت به پرونده
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

        <?php if ($error_message && !$entry): ?>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>
                    بازگشت به داشبورد
                </a>
            </div>
        <?php elseif ($entry): ?>
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        ویرایش ورودی: <?php echo htmlspecialchars($entry['entry_title']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="entry_title" class="form-label">عنوان ورودی *</label>
                                    <input type="text" class="form-control" id="entry_title" name="entry_title" 
                                           value="<?php echo htmlspecialchars($entry['entry_title']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">توضیحات *</label>
                                    <textarea class="form-control" id="description" name="description" rows="6" 
                                              required><?php echo htmlspecialchars($entry['description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Attachments Section -->
                        <div class="mb-4">
                            <label class="form-label">پیوست‌های جدید</label>
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
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        ذخیره تغییرات
                                    </button>
                                    <a href="view_case.php?case_id=<?php echo $case['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        انصراف
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Attachments -->
            <?php if (!empty($attachments)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-paperclip me-2"></i>
                            پیوست‌های موجود (<?php echo count($attachments); ?> فایل)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($attachments as $attachment): ?>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="download.php?id=<?php echo $attachment['id']; ?>" 
                                           class="btn btn-outline-primary" title="دانلود">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="delete_attachment.php?id=<?php echo $attachment['id']; ?>&return_url=<?php echo urlencode('edit_case_entry.php?id=' . $entry_id); ?>" 
                                           class="btn btn-outline-danger" title="حذف"
                                           onclick="return confirm('آیا مطمئن هستید که می‌خواهید این پیوست را حذف کنید؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Entry Info Summary -->
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
                            <div class="info-item">
                                <div class="info-label">پرونده:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['case_title']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">شاکی:</div>
                                <div class="info-value"><?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">تاریخ ثبت:</div>
                                <div class="info-value"><?php echo JalaliDate::formatJalaliDate($entry['created_at']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">شناسه ورودی:</div>
                                <div class="info-value"><?php echo $entry['id']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
</body>
</html>
