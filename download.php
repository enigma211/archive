<?php
/**
 * Secure File Download
 * Handles secure file downloads with proper headers and access control
 */

require_once 'includes/SessionHelper.php';
require_once 'config.php';

// Start session
SessionHelper::start();

// Check if user is logged in
if (!SessionHelper::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get attachment ID from URL
$attachment_id = $_GET['id'] ?? '';

if (empty($attachment_id) || !is_numeric($attachment_id)) {
    header('HTTP/1.0 404 Not Found');
    echo 'فایل مورد نظر یافت نشد';
    exit();
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'خطا در اتصال به پایگاه داده';
    exit();
}

try {
    // Get attachment information
    $query = "SELECT a.*, ce.case_id, c.individual_id 
              FROM attachments a 
              JOIN case_entries ce ON a.entry_id = ce.id 
              JOIN cases c ON ce.case_id = c.id 
              WHERE a.id = :attachment_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attachment_id', $attachment_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('HTTP/1.0 404 Not Found');
        echo 'فایل مورد نظر یافت نشد';
        exit();
    }
    
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if file exists on disk
    $file_path = $attachment['file_path'];
    if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        echo 'فایل در سرور یافت نشد';
        exit();
    }
    
    // Get file information
    $file_size = filesize($file_path);
    $original_filename = $attachment['original_filename'];
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    
    // Generate display filename (use server filename without path)
    $server_filename = basename($file_path);
    $display_filename = $server_filename;
    
    // Determine MIME type based on file extension
    $mime_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    $mime_type = $mime_types[strtolower($file_extension)] ?? 'application/octet-stream';
    
    // Set headers for file download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $display_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output file content
    readfile($file_path);
    exit();
    
} catch (Exception $e) {
    // Log error
    error_log("Download error: " . $e->getMessage());
    
    header('HTTP/1.0 500 Internal Server Error');
    echo 'خطا در دانلود فایل';
    exit();
}
?>
