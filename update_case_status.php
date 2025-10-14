<?php
/**
 * Update Case Status
 * Handles updating case status from view_case.php
 */

require_once 'config.php';
require_once 'includes/Auth.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditCases();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?error=' . urlencode('درخواست نامعتبر'));
    exit();
}

// Get form data
$case_id = $_POST['case_id'] ?? '';
$status = $_POST['status'] ?? '';

// Validation
$errors = [];

if (empty($case_id) || !is_numeric($case_id)) {
    $errors[] = 'شناسه پرونده نامعتبر است';
}

if (!in_array($status, ['open', 'in_progress', 'closed'])) {
    $errors[] = 'وضعیت نامعتبر است';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode($error_message));
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify case exists
    $query = "SELECT id, status FROM cases WHERE id = :case_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':case_id', $case_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("پرونده مورد نظر یافت نشد");
    }
    
    $current_case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if status is actually changing
    if ($current_case['status'] === $status) {
        header('Location: view_case.php?case_id=' . $case_id . '&success=' . urlencode('وضعیت پرونده تغییر نکرده است'));
        exit();
    }
    
    // Update case status
    $query = "UPDATE cases SET status = :status WHERE id = :case_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':case_id', $case_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در به‌روزرسانی وضعیت پرونده");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Success - redirect back to the case page
    $status_text = '';
    switch ($status) {
        case 'open':
            $status_text = 'باز';
            break;
        case 'in_progress':
            $status_text = 'در حال بررسی';
            break;
        case 'closed':
            $status_text = 'بسته';
            break;
    }
    
    header('Location: view_case.php?case_id=' . $case_id . '&success=' . urlencode('وضعیت پرونده به "' . $status_text . '" تغییر یافت'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log error
    error_log("Update case status error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: view_case.php?case_id=' . $case_id . '&error=' . urlencode('خطا در تغییر وضعیت: ' . $e->getMessage()));
    exit();
}
?>
