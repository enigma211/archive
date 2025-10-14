<?php
/**
 * Update Case Type and Stage
 * Updates the case type and stage based on user selection
 */

require_once 'includes/Auth.php';
require_once 'config.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditCases();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?error=' . urlencode('درخواست نامعتبر است'));
    exit();
}

// Get parameters
$case_id = $_POST['case_id'] ?? '';
$case_type = $_POST['case_type'] ?? '';
$case_stage = $_POST['case_stage'] ?? '';

// Validation
if (empty($case_id) || !is_numeric($case_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه پرونده نامعتبر است'));
    exit();
}

// Validate case_type
$valid_types = ['اظهارنامه', 'دادخواست بدوی', 'اعاده دادرسی'];
if (!empty($case_type) && !in_array($case_type, $valid_types)) {
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('نوع پرونده نامعتبر است'));
    exit();
}

// Validate case_stage based on case_type
$valid_stages = [
    'اظهارنامه' => ['جاری', 'پاسخ تهیه شد'],
    'دادخواست بدوی' => ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه'],
    'اعاده دادرسی' => ['جاری', 'لایحه تهیه شده', 'دادنامه صادر شده', 'مختومه']
];

if (!empty($case_stage) && !empty($case_type)) {
    if (!isset($valid_stages[$case_type]) || !in_array($case_stage, $valid_stages[$case_type])) {
        header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('مرحله پرونده نامعتبر است'));
        exit();
    }
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Update case type and stage
    $query = "UPDATE cases SET case_type = :case_type, case_stage = :case_stage WHERE id = :case_id";
    $stmt = $conn->prepare($query);
    
    $case_type_param = !empty($case_type) ? $case_type : null;
    $case_stage_param = !empty($case_stage) ? $case_stage : null;
    
    $stmt->bindParam(':case_type', $case_type_param);
    $stmt->bindParam(':case_stage', $case_stage_param);
    $stmt->bindParam(':case_id', $case_id);
    
    if ($stmt->execute()) {
        header('Location: view_case.php?case_id=' . $case_id . '&success=' . urlencode('نوع و مرحله پرونده با موفقیت به‌روزرسانی شد'));
        exit();
    } else {
        header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('خطا در به‌روزرسانی اطلاعات'));
        exit();
    }
} catch (Exception $e) {
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('خطا: ' . $e->getMessage()));
    exit();
}
?>
