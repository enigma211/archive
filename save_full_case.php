<?php
/**
 * Save Full Case with Entry and Attachments
 * Handles complete case creation with transaction support
 */

require_once 'includes/SessionHelper.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';
require_once 'includes/AuditLogger.php';

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
$entry_title = trim($_POST['entry_title'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadline_days = $_POST['deadline_days'] ?? '';
$complaint_date = $_POST['complaint_date'] ?? '';

// Server-side validation
$errors = [];

if (empty($individual_id) || !is_numeric($individual_id)) {
    $errors[] = 'شناسه فرد نامعتبر است';
}

if (empty($case_title)) {
    $errors[] = 'عنوان پرونده اجباری است';
}

if (empty($entry_title)) {
    $errors[] = 'عنوان دادخواست اجباری است';
}

if (empty($description)) {
    $errors[] = 'توضیحات دادخواست اجباری است';
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

// Deadline is now optional - validate only if provided
if (!empty($deadline_days) && (!is_numeric($deadline_days) || $deadline_days < 1)) {
    $errors[] = 'مهلت پاسخ باید یک عدد مثبت باشد';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: handle_new_case.php?error=' . urlencode($error_message));
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    header('Location: dashboard.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get current user
    $user = SessionHelper::getCurrentUser();
    $user_id = $user['id'];
    
    // Step 1: Insert new case with deadline (if provided)
    require_once 'includes/DeadlineHelper.php';
    
    // Calculate deadline only if deadline_days is provided and greater than 0
    $deadline_date = null;
    $deadline_days_param = null;
    if (!empty($deadline_days) && $deadline_days > 0) {
        $deadline_date = DeadlineHelper::calculateDeadlineDate(date('Y-m-d H:i:s'), $deadline_days);
        $deadline_days_param = (int)$deadline_days;
    }
    
    $query = "INSERT INTO cases (individual_id, case_title, status, deadline_days, deadline_date, complaint_date) 
              VALUES (:individual_id, :case_title, 'open', :deadline_days, :deadline_date, :complaint_date)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':individual_id', $individual_id);
    $stmt->bindParam(':case_title', $case_title);
    $stmt->bindParam(':deadline_days', $deadline_days_param, PDO::PARAM_INT);
    $stmt->bindParam(':complaint_date', $complaint_date);
    $stmt->bindParam(':deadline_date', $deadline_date);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert case");
    }
    
    $case_id = $conn->lastInsertId();
    // Audit: case created
    AuditLogger::log('case_create', 'case', $case_id, [
        'individual_id' => (int)$individual_id,
        'case_title' => $case_title,
        'deadline_days' => isset($deadline_days_param)? $deadline_days_param : null,
        'deadline_date' => $deadline_date,
        'complaint_date' => $complaint_date
    ]);
    
    // Step 2: Insert case entry
    $jalali_date = JalaliDate::getCurrentJalaliDate();
    $query = "INSERT INTO case_entries (case_id, user_id, entry_title, description, created_at_jalali) 
              VALUES (:case_id, :user_id, :entry_title, :description, :created_at_jalali)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':case_id', $case_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':entry_title', $entry_title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':created_at_jalali', $jalali_date);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert case entry");
    }
    
    $entry_id = $conn->lastInsertId();
    // Audit: initial entry created
    AuditLogger::log('case_entry_create', 'case_entry', $entry_id, [
        'case_id' => (int)$case_id,
        'entry_title' => $entry_title
    ]);
    
    // Step 3: Handle file uploads
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
                        throw new Exception("Failed to insert attachment record");
                    }
                    // Audit: attachment added
                    AuditLogger::log('attachment_add', 'attachment', $conn->lastInsertId(), [
                        'entry_id' => (int)$entry_id,
                        'original_filename' => $original_filename,
                        'file_path' => $file_path
                    ]);
                } else {
                    throw new Exception("Failed to move uploaded file: " . $original_filename);
                }
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect to the newly created case
    header('Location: view_case.php?case_id=' . $case_id . '&success=' . urlencode('پرونده و دادخواست اولیه با موفقیت ایجاد شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Save full case error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: handle_new_case.php?error=' . urlencode('خطا در ایجاد پرونده: ' . $e->getMessage()));
    exit();
}
?>
