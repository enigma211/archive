<?php
/**
 * Delete Attachment
 * Securely deletes attachment files and database records
 */

require_once 'includes/Auth.php';
require_once 'config.php';

// Initialize Auth and check permissions
$auth = new Auth();
$auth->requireEditCaseEntries();

// Get attachment ID from URL
$attachment_id = $_GET['id'] ?? '';

if (empty($attachment_id) || !is_numeric($attachment_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه پیوست نامعتبر است'));
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
    // Get attachment information with case details
    $query = "SELECT a.*, ce.case_id 
              FROM attachments a 
              JOIN case_entries ce ON a.entry_id = ce.id 
              WHERE a.id = :attachment_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attachment_id', $attachment_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: dashboard.php?error=' . urlencode('پیوست مورد نظر یافت نشد'));
        exit();
    }
    
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    $case_id = $attachment['case_id'];
    $file_path = $attachment['file_path'];
    
    // Start transaction
    $conn->beginTransaction();
    
    // Delete the physical file if it exists
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception("خطا در حذف فایل از سرور");
        }
    }
    
    // Delete the database record
    $query = "DELETE FROM attachments WHERE id = :attachment_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attachment_id', $attachment_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در حذف رکورد از پایگاه داده");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to appropriate page
    $return_url = $_GET['return_url'] ?? "view_case.php?case_id=" . $case_id;
    header('Location: ' . $return_url . '&success=' . urlencode('پیوست با موفقیت حذف شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Delete attachment error: " . $e->getMessage());
    
    // Redirect with error
    $return_url = $_GET['return_url'] ?? "dashboard.php";
    header('Location: ' . $return_url . '&error=' . urlencode('خطا در حذف پیوست: ' . $e->getMessage()));
    exit();
}
?>
