<?php
/**
 * Add Individual Script
 * Handles form submission for adding new individuals
 */

require_once 'includes/SessionHelper.php';
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

    // If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: add_individual_page.php?error=' . urlencode($error_message));
    exit();
}

try {
    // Connect to database
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Check if national ID already exists
    $query = "SELECT id FROM individuals WHERE national_id = :national_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':national_id', $national_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header('Location: add_individual_page.php?error=' . urlencode('کد ملی تکراری است'));
        exit();
    }
    
    // Insert new individual using prepared statement
    $query = "INSERT INTO individuals (first_name, last_name, national_id, mobile_number, father_name, university_major, exam_name) 
              VALUES (:first_name, :last_name, :national_id, :mobile_number, :father_name, :university_major, :exam_name)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':national_id', $national_id);
    $stmt->bindParam(':mobile_number', $mobile_number);
    $stmt->bindParam(':father_name', $father_name);
    $stmt->bindParam(':university_major', $university_major);
    $stmt->bindParam(':exam_name', $exam_name);
    
    if ($stmt->execute()) {
        $individual_id = $conn->lastInsertId();
        
        // Audit: individual created
        AuditLogger::log('individual_create', 'individual', $individual_id, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'national_id' => $national_id
        ]);
        
        // Success - redirect with success message
        header('Location: add_individual_page.php?success=' . urlencode('فرد جدید با موفقیت اضافه شد'));
        exit();
    } else {
        throw new Exception("Failed to insert individual");
    }
    
} catch (Exception $e) {
    // Log error (in production, you might want to log this to a file)
    error_log("Add individual error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: add_individual_page.php?error=' . urlencode('خطا در ذخیره اطلاعات'));
    exit();
}
?>
