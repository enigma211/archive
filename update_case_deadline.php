<?php
/**
 * Update Case Deadline Script
 * Accepts a Jalali final deadline date and updates cases.deadline_date accordingly
 */

require_once 'includes/SessionHelper.php';
require_once 'includes/JalaliDate.php';
require_once 'includes/Auth.php';
require_once 'config.php';

// Start session and check auth
SessionHelper::start();
$auth = new Auth();
$auth->requireEditCases();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?error=' . urlencode('درخواست نامعتبر'));
    exit();
}

$case_id = $_POST['case_id'] ?? '';
$deadline_jalali = trim($_POST['deadline_jalali'] ?? '');

if (empty($case_id) || !is_numeric($case_id)) {
    header('Location: dashboard.php?error=' . urlencode('شناسه پرونده نامعتبر است'));
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('عدم اتصال به پایگاه داده');
    }

    // Convert Jalali to Gregorian if provided; allow clearing when empty
    $deadline_date = null;
    if ($deadline_jalali !== '') {
        $greg = JalaliDate::jalaliStringToGregorianDate($deadline_jalali);
        if (empty($greg)) {
            header('Location: view_case.php?case_id=' . urlencode($case_id) . '&error=' . urlencode('تاریخ مهلت نامعتبر است. فرمت صحیح: YYYY/MM/DD'));
            exit();
        }
        $deadline_date = $greg; // Y-m-d
    }

    // Update deadline_date directly; reset deadline_days to NULL (days are derived)
    $query = "UPDATE cases SET deadline_date = :deadline_date, deadline_days = NULL WHERE id = :case_id";
    $stmt = $conn->prepare($query);
    if ($deadline_date === null) {
        $stmt->bindValue(':deadline_date', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':deadline_date', $deadline_date);
    }
    $stmt->bindValue(':case_id', (int)$case_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: view_case.php?case_id=' . urlencode($case_id) . '&success=' . urlencode('مهلت پرونده به‌روزرسانی شد'));
        exit();
    } else {
        header('Location: view_case.php?case_id=' . urlencode($case_id) . '&error=' . urlencode('خطا در به‌روزرسانی مهلت'));
        exit();
    }
} catch (Exception $e) {
    error_log('Update deadline error: ' . $e->getMessage());
    header('Location: view_case.php?case_id=' . urlencode($case_id) . '&error=' . urlencode('خطای غیرمنتظره هنگام به‌روزرسانی مهلت'));
    exit();
}
