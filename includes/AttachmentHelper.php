<?php
/**
 * Attachment Helper Class
 * Handles file upload validation and settings
 */

class AttachmentHelper {
    
    /**
     * Get allowed file extensions from settings
     */
    public static function getAllowedExtensions() {
        global $conn;
        
        if (!$conn) {
            return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt']; // Default fallback
        }
        
        try {
            $query = "SELECT setting_value FROM settings WHERE setting_key = 'allowed_extensions'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['setting_value'])) {
                $extensions = array_map('trim', explode(',', strtolower($result['setting_value'])));
                return array_filter($extensions); // Remove empty values
            }
        } catch (Exception $e) {
            error_log("Error getting allowed extensions: " . $e->getMessage());
        }
        
        return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt']; // Default fallback
    }
    
    /**
     * Get maximum file size from settings (in MB)
     */
    public static function getMaxFileSize() {
        global $conn;
        
        if (!$conn) {
            return 10; // Default fallback (10MB)
        }
        
        try {
            $query = "SELECT setting_value FROM settings WHERE setting_key = 'max_file_size'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['setting_value'])) {
                return (int)$result['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Error getting max file size: " . $e->getMessage());
        }
        
        return 10; // Default fallback (10MB)
    }
    
    /**
     * Get maximum file size in bytes
     */
    public static function getMaxFileSizeBytes() {
        return self::getMaxFileSize() * 1024 * 1024; // Convert MB to bytes
    }
    
    /**
     * Validate file extension
     */
    public static function isValidExtension($filename) {
        $allowed_extensions = self::getAllowedExtensions();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return in_array($extension, $allowed_extensions);
    }
    
    /**
     * Validate file size
     */
    public static function isValidFileSize($file_size) {
        return $file_size <= self::getMaxFileSizeBytes();
    }
    
    /**
     * Validate uploaded file
     */
    public static function validateFile($file) {
        $errors = [];
        
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'خطا در آپلود فایل';
            return $errors;
        }
        
        if (!self::isValidExtension($file['name'])) {
            $allowed_extensions = self::getAllowedExtensions();
            $errors[] = 'نوع فایل مجاز نیست. پسوندهای مجاز: ' . implode(', ', $allowed_extensions);
        }
        
        if (!self::isValidFileSize($file['size'])) {
            $max_size = self::getMaxFileSize();
            $errors[] = "حجم فایل بیش از حد مجاز است. حداکثر حجم مجاز: {$max_size} مگابایت";
        }
        
        return $errors;
    }
    
    /**
     * Get file upload error message in Persian
     */
    public static function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم فایل بیش از حد مجاز است';
            case UPLOAD_ERR_PARTIAL:
                return 'فایل به طور کامل آپلود نشده است';
            case UPLOAD_ERR_NO_FILE:
                return 'هیچ فایلی انتخاب نشده است';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'پوشه موقت برای آپلود وجود ندارد';
            case UPLOAD_ERR_CANT_WRITE:
                return 'خطا در نوشتن فایل روی سرور';
            case UPLOAD_ERR_EXTENSION:
                return 'آپلود فایل توسط یک افزونه متوقف شد';
            default:
                return 'خطای نامشخص در آپلود فایل';
        }
    }
    
    /**
     * Format file size for display
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' مگابایت';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' کیلوبایت';
        } else {
            return $bytes . ' بایت';
        }
    }
    
    /**
     * Get settings summary for display
     */
    public static function getSettingsSummary() {
        $allowed_extensions = self::getAllowedExtensions();
        $max_file_size = self::getMaxFileSize();
        
        return [
            'allowed_extensions' => implode(', ', $allowed_extensions),
            'max_file_size' => $max_file_size . ' مگابایت',
            'max_file_size_bytes' => self::getMaxFileSizeBytes()
        ];
    }
    
    /**
     * Create monthly upload directory structure
     * Format: uploads/cases/YYYY/MM/
     */
    public static function createMonthlyUploadDir() {
        $current_date = date('Y/m'); // Only year and month
        $upload_dir = "uploads/cases/{$current_date}/";
        
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("خطا در ایجاد پوشه آپلود ماهانه");
            }
        }
        
        return $upload_dir;
    }
    
    /**
     * Get current monthly upload directory
     */
    public static function getCurrentMonthlyUploadDir() {
        $current_date = date('Y/m'); // Only year and month
        return "uploads/cases/{$current_date}/";
    }
}
?>
