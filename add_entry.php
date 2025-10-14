<?php
/**
 * Add New Case Entry
 * Handles adding new entries to existing cases with attachments
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
$case_id = $_POST['case_id'] ?? '';
$entry_title = trim($_POST['entry_title'] ?? '');
$description = trim($_POST['description'] ?? '');

// Server-side validation
$errors = [];

if (empty($case_id) || !is_numeric($case_id)) {
    $errors[] = 'شناسه پرونده نامعتبر است';
}

if (empty($entry_title)) {
    $errors[] = 'عنوان ورودی اجباری است';
}

if (empty($description)) {
    $errors[] = 'توضیحات ورودی اجباری است';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode($error_message));
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
    
    // Verify case exists
    $query = "SELECT id FROM cases WHERE id = :case_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':case_id', $case_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("پرونده مورد نظر یافت نشد");
    }
    
    // Step 1: Insert case entry
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
        throw new Exception("خطا در ثبت ورودی");
    }
    
    $entry_id = $conn->lastInsertId();
    
    // Step 2: Handle file uploads
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
    
    // Success - redirect back to the case page
    header('Location: view_case.php?case_id=' . $case_id . '&success=' . urlencode('ورودی جدید با موفقیت اضافه شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Add entry error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('خطا در افزودن ورودی: ' . $e->getMessage()));
    exit();
}
?>
