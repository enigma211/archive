<?php
/**
 * Installation Script for Case Type and Stage Fields
 * Run this file once to add the new fields to the cases table
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='fa' dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>نصب فیلدهای جدید</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body { font-family: 'Tahoma', sans-serif; padding: 50px; }</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h3>نصب فیلدهای نوع و مرحله پرونده</h3>";
echo "</div>";
echo "<div class='card-body'>";

try {
    // Check if fields already exist
    $query = "SHOW COLUMNS FROM cases LIKE 'case_type'";
    $stmt = $pdo->query($query);
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='alert alert-warning'>";
        echo "<i class='fas fa-exclamation-triangle'></i> ";
        echo "فیلدهای case_type و case_stage قبلاً نصب شده‌اند.";
        echo "</div>";
    } else {
        // Add case_type field
        $query1 = "ALTER TABLE cases ADD COLUMN case_type VARCHAR(50) DEFAULT NULL COMMENT 'نوع پرونده: اظهارنامه، دادخواست بدوی، اعاده دادرسی'";
        $pdo->exec($query1);
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check'></i> ";
        echo "فیلد case_type با موفقیت اضافه شد.";
        echo "</div>";
        
        // Add case_stage field
        $query2 = "ALTER TABLE cases ADD COLUMN case_stage VARCHAR(50) DEFAULT NULL COMMENT 'مرحله پرونده بر اساس نوع'";
        $pdo->exec($query2);
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check'></i> ";
        echo "فیلد case_stage با موفقیت اضافه شد.";
        echo "</div>";
        
        echo "<div class='alert alert-info mt-3'>";
        echo "<h5>نصب با موفقیت انجام شد!</h5>";
        echo "<p>فیلدهای جدید به جدول cases اضافه شدند. اکنون می‌توانید:</p>";
        echo "<ul>";
        echo "<li>به صفحه جزئیات پرونده بروید</li>";
        echo "<li>نوع پرونده را انتخاب کنید (اظهارنامه، دادخواست بدوی، اعاده دادرسی)</li>";
        echo "<li>مرحله پرونده را بر اساس نوع انتخاب کنید</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // Show current table structure
    echo "<div class='mt-4'>";
    echo "<h5>ساختار فعلی جدول cases:</h5>";
    echo "<table class='table table-bordered table-sm'>";
    echo "<thead><tr><th>نام فیلد</th><th>نوع</th><th>توضیحات</th></tr></thead>";
    echo "<tbody>";
    
    $query = "SHOW FULL COLUMNS FROM cases";
    $stmt = $pdo->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Comment']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<i class='fas fa-exclamation-circle'></i> ";
    echo "<strong>خطا:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>راهنمای نصب دستی:</h5>";
    echo "<p>اگر خطا دریافت کردید، می‌توانید از طریق phpMyAdmin این دستورات را اجرا کنید:</p>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo "ALTER TABLE cases ADD COLUMN case_type VARCHAR(50) DEFAULT NULL COMMENT 'نوع پرونده';\n";
    echo "ALTER TABLE cases ADD COLUMN case_stage VARCHAR(50) DEFAULT NULL COMMENT 'مرحله پرونده';";
    echo "</pre>";
    echo "</div>";
}

echo "<div class='mt-4'>";
echo "<a href='dashboard.php' class='btn btn-primary'>بازگشت به داشبورد</a> ";
echo "<a href='cases.php' class='btn btn-secondary'>مشاهده پرونده‌ها</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
