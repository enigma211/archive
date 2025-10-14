<?php
// تنظیم منطقه زمانی به تهران
date_default_timezone_set('Asia/Tehran');

// تنظیمات پایگاه داده
define('DB_HOST', 'localhost');
define('DB_NAME', 'complaint_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// تنظیمات سیستم
define('SITE_URL', 'https://yourdomain.com/archive');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// تنظیمات امنیتی
define('SESSION_TIMEOUT', 3600); // 1 ساعت
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 دقیقه

// تنظیمات تاریخ
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('JALALI_DATE_FORMAT', 'Y/m/d');
define('JALALI_DATETIME_FORMAT', 'Y/m/d H:i:s');

// کلاس Database
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            echo "خطا در اتصال به پایگاه داده: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// اتصال به پایگاه داده
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
}

// تابع تبدیل تاریخ میلادی به جلالی
function gregorianToJalali($gregorian_date) {
    if (empty($gregorian_date)) {
        return '';
    }
    
    try {
        // بررسی وجود IntlDateFormatter
        if (class_exists('IntlDateFormatter')) {
            // ایجاد شیء DateTime از تاریخ میلادی
            $date = new DateTime($gregorian_date);
            
            // ایجاد formatter برای تاریخ جلالی
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd'
            );
            
            return $formatter->format($date);
        } else {
            // استفاده از JalaliDate class به عنوان fallback
            require_once 'includes/JalaliDate.php';
            $date = new DateTime($gregorian_date);
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');
            
            $jalali = JalaliDate::gregorianToJalali($year, $month, $day);
            return sprintf('%04d/%02d/%02d', $jalali[0], $jalali[1], $jalali[2]);
        }
    } catch (Exception $e) {
        error_log("خطا در تبدیل تاریخ: " . $e->getMessage());
        return $gregorian_date;
    }
}

// تابع تبدیل تاریخ و زمان میلادی به جلالی
function gregorianToJalaliDateTime($gregorian_datetime) {
    if (empty($gregorian_datetime)) {
        return '';
    }
    
    try {
        // بررسی وجود IntlDateFormatter
        if (class_exists('IntlDateFormatter')) {
            $date = new DateTime($gregorian_datetime);
            
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::SHORT,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd HH:mm'
            );
            
            return $formatter->format($date);
        } else {
            // استفاده از JalaliDate class به عنوان fallback
            require_once 'includes/JalaliDate.php';
            $date = new DateTime($gregorian_datetime);
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');
            $hour = $date->format('H');
            $minute = $date->format('i');
            
            $jalali = JalaliDate::gregorianToJalali($year, $month, $day);
            return sprintf('%04d/%02d/%02d %02d:%02d', $jalali[0], $jalali[1], $jalali[2], $hour, $minute);
        }
    } catch (Exception $e) {
        error_log("خطا در تبدیل تاریخ و زمان: " . $e->getMessage());
        return $gregorian_datetime;
    }
}

// تابع دریافت تاریخ و زمان فعلی میلادی
function getCurrentDateTime() {
    return date(DATE_FORMAT);
}

// تابع دریافت تاریخ فعلی میلادی
function getCurrentDate() {
    return date('Y-m-d');
}

// تابع دریافت تاریخ فعلی جلالی
function getCurrentJalaliDate() {
    return gregorianToJalali(getCurrentDate());
}

// تابع دریافت تاریخ و زمان فعلی جلالی
function getCurrentJalaliDateTime() {
    return gregorianToJalaliDateTime(getCurrentDateTime());
}
?>
