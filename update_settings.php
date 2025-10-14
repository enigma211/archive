<?php
/**
 * Update Settings Handler
 * Processes form submission for updating general settings
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: general_settings.php?error=' . urlencode('درخواست نامعتبر'));
    exit();
}

// Get form data
$system_name = trim($_POST['system_name'] ?? '');
$footer_text = trim($_POST['footer_text'] ?? '');
$ssl_enabled = isset($_POST['ssl_enabled']) ? '1' : '0';
$font_size = trim($_POST['font_size'] ?? '14');
$deadline_warning_days = trim($_POST['deadline_warning_days'] ?? '5');
$deadline_urgent_days = trim($_POST['deadline_urgent_days'] ?? '2');
$allowed_extensions = trim($_POST['allowed_extensions'] ?? '');
$max_file_size = trim($_POST['max_file_size'] ?? '10');

// Server-side validation
$errors = [];

if (empty($system_name)) {
    $errors[] = 'نام سیستم اجباری است';
} elseif (strlen($system_name) < 3) {
    $errors[] = 'نام سیستم باید حداقل 3 کاراکتر باشد';
}

if (empty($footer_text)) {
    $errors[] = 'متن فوتر اجباری است';
} elseif (strlen($footer_text) < 3) {
    $errors[] = 'متن فوتر باید حداقل 3 کاراکتر باشد';
}

if (!in_array($font_size, ['12', '13', '14', '15', '16'])) {
    $errors[] = 'اندازه فونت نامعتبر است';
}

if (!is_numeric($deadline_warning_days) || $deadline_warning_days < 1 || $deadline_warning_days > 30) {
    $errors[] = 'روزهای هشدار باید بین 1 تا 30 باشد';
}

if (!is_numeric($deadline_urgent_days) || $deadline_urgent_days < 1 || $deadline_urgent_days > 10) {
    $errors[] = 'روزهای فوری باید بین 1 تا 10 باشد';
}

if (is_numeric($deadline_urgent_days) && is_numeric($deadline_warning_days) && $deadline_urgent_days >= $deadline_warning_days) {
    $errors[] = 'روزهای فوری باید کمتر از روزهای هشدار باشد';
}

if (empty($allowed_extensions)) {
    $errors[] = 'پسوندهای مجاز اجباری است';
} else {
    // Validate extensions format (should be comma-separated, no spaces, lowercase)
    $extensions = array_map('trim', explode(',', strtolower($allowed_extensions)));
    $extensions = array_filter($extensions); // Remove empty values
    
    if (empty($extensions)) {
        $errors[] = 'حداقل یک پسوند مجاز باید تعریف شود';
    } else {
        // Check for valid extensions (alphanumeric only)
        foreach ($extensions as $ext) {
            if (!preg_match('/^[a-z0-9]+$/', $ext)) {
                $errors[] = 'پسوند "' . $ext . '" نامعتبر است. فقط حروف و اعداد مجاز است';
                break;
            }
        }
    }
}

if (!in_array($max_file_size, ['1', '2', '5', '10', '20', '50', '100'])) {
    $errors[] = 'حداکثر حجم فایل نامعتبر است';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: general_settings.php?error=' . urlencode($error_message));
    exit();
}

// Connect to database
$conn = getConnection();

if (!$conn) {
    header('Location: general_settings.php?error=' . urlencode('خطا در اتصال به پایگاه داده'));
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Update system name
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('system_name', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $system_name);
    $stmt->execute();
    
    // Update footer text
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('footer_text', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $footer_text);
    $stmt->execute();
    
    // Update SSL setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('ssl_enabled', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $ssl_enabled);
    $stmt->execute();
    
    // Update font size setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('font_size', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $font_size);
    $stmt->execute();
    
    // Update allowed extensions setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('allowed_extensions', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $allowed_extensions);
    $stmt->execute();
    
    // Update max file size setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('max_file_size', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $max_file_size);
    $stmt->execute();
    
    // Update deadline warning days setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('deadline_warning_days', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $deadline_warning_days);
    $stmt->execute();
    
    // Update deadline urgent days setting
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('deadline_urgent_days', :value) 
              ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value', $deadline_urgent_days);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect with success message
    header('Location: general_settings.php?success=' . urlencode('تنظیمات با موفقیت به‌روزرسانی شد'));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log error
    error_log("Settings update error: " . $e->getMessage());
    
    // Redirect with error
    header('Location: general_settings.php?error=' . urlencode('خطا در ذخیره تنظیمات'));
    exit();
}
?>
