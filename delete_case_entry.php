<?php
/**
 * Delete Case Entry
 * Securely deletes case entry and all its attachments
 */

require_once 'includes/Auth.php';
require_once 'config.php';
require_once 'includes/AuditLogger.php';

// Initialize Auth and check permissions (Admin only)
$auth = new Auth();
$auth->requireAdmin();

// Get entry ID from URL
$entry_id = $_GET['id'] ?? '';

if (empty($entry_id) || !is_numeric($entry_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه ورودی نامعتبر است'));
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
    // Get entry information with case details
    $query = "SELECT ce.*, c.id as case_id 
              FROM case_entries ce 
              JOIN cases c ON ce.case_id = c.id 
              WHERE ce.id = :entry_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':entry_id', $entry_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: dashboard.php?error=' . urlencode('ورودی مورد نظر یافت نشد'));
        exit();
    }
    
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    $case_id = $entry['case_id'];
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get all attachments for this entry
    $query = "SELECT * FROM attachments WHERE entry_id = :entry_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':entry_id', $entry_id);
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Delete all physical files
    foreach ($attachments as $attachment) {
        $file_path = $attachment['file_path'];
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                throw new Exception("خطا در حذف فایل: " . $attachment['original_filename']);
            }
        }
    }
    
    // Delete all attachment records
    $query = "DELETE FROM attachments WHERE entry_id = :entry_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':entry_id', $entry_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در حذف پیوست‌ها از پایگاه داده");
    }
    
    // Delete the case entry record
    $query = "DELETE FROM case_entries WHERE id = :entry_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':entry_id', $entry_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در حذف ورودی از پایگاه داده");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Audit: entry deleted
    AuditLogger::log('case_entry_delete', 'case_entry', $entry_id, [
        'case_id' => (int)$case_id,
        'attachments_deleted' => count($attachments)
    ]);
    
    // Success - redirect back to case view
    $return_url = $_GET['return_url'] ?? "view_case.php?case_id=" . $case_id;
    header('Location: ' . $return_url . '&success=' . urlencode('ورودی پرونده با موفقیت حذف شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Delete case entry error: " . $e->getMessage());
    
    // Redirect with error
    $return_url = $_GET['return_url'] ?? "dashboard.php";
    header('Location: ' . $return_url . '&error=' . urlencode('خطا در حذف ورودی: ' . $e->getMessage()));
    exit();
}
?>
