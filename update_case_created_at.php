<?php
/**
 * Update Case Creation Date
 * Accepts Jalali date input and updates cases.created_at (Gregorian)
 */

require_once 'includes/Auth.php';
require_once 'config.php';
require_once 'includes/JalaliDate.php';
require_once 'includes/DeadlineHelper.php';

$auth = new Auth();
// Require permission to edit cases
$auth->requireEditCases();

// Ensure timezone is consistent
date_default_timezone_set('Asia/Tehran');

// Helper to redirect back with message
function redirect_back($case_id, $message, $type = 'success') {
    $param = $type === 'success' ? 'success' : 'error';
    header('Location: view_case.php?case_id=' . urlencode($case_id) . '&' . $param . '=' . urlencode($message));
    exit();
}

$case_id = $_POST['case_id'] ?? null;
$created_at_jalali = trim($_POST['created_at_jalali'] ?? '');

if (empty($case_id) || !is_numeric($case_id)) {
    redirect_back($case_id ?: '', 'شناسه پرونده نامعتبر است', 'error');
}

if (empty($created_at_jalali)) {
    redirect_back($case_id, 'تاریخ جلالی وارد نشده است', 'error');
}

// Validate Jalali format YYYY/MM/DD and convert robustly
if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $created_at_jalali)) {
    redirect_back($case_id, 'فرمت تاریخ معتبر نیست. فرمت صحیح: YYYY/MM/DD', 'error');
}

// Convert to Gregorian using helper (Intl first, fallback manual)
$gregorian_date = JalaliDate::jalaliStringToGregorianDate($created_at_jalali);
if (empty($gregorian_date)) {
    redirect_back($case_id, 'خطا در تبدیل تاریخ جلالی به میلادی', 'error');
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        redirect_back($case_id, 'خطا در اتصال به پایگاه داده', 'error');
    }

    // Ensure case exists and get current deadline_days (to recalc deadline_date)
    $query = 'SELECT id, deadline_days FROM cases WHERE id = :case_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':case_id', $case_id);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        redirect_back($case_id, 'پرونده مورد نظر یافت نشد', 'error');
    }
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    // Recalculate deadline_date if deadline_days exists
    $deadline_date = null;
    if (!empty($case['deadline_days'])) {
        $deadline_date = DeadlineHelper::calculateDeadlineDate($gregorian_date, (int)$case['deadline_days']);
    }

    // Update
    $update = 'UPDATE cases SET created_at = :created_at, deadline_date = :deadline_date WHERE id = :case_id';
    $stmt2 = $conn->prepare($update);
    $stmt2->bindParam(':created_at', $gregorian_date);
    $stmt2->bindParam(':deadline_date', $deadline_date);
    $stmt2->bindParam(':case_id', $case_id);

    if ($stmt2->execute()) {
        redirect_back($case_id, 'تاریخ ایجاد پرونده با موفقیت به‌روزرسانی شد');
    } else {
        redirect_back($case_id, 'خطا در ذخیره‌سازی تاریخ ایجاد پرونده', 'error');
    }

} catch (Exception $e) {
    redirect_back($case_id, 'خطای غیرمنتظره: ' . $e->getMessage(), 'error');
}
