<?php
/**
 * Deadline Helper Class
 * Handles deadline calculations and status for cases
 */

class DeadlineHelper {
    
    /**
     * Calculate deadline date based on creation date and days
     */
    public static function calculateDeadlineDate($created_at, $deadline_days) {
        if (!$created_at || !$deadline_days) {
            return null;
        }
        
        $created_date = new DateTime($created_at);
        $deadline_date = clone $created_date;
        $deadline_date->add(new DateInterval('P' . $deadline_days . 'D'));
        
        return $deadline_date->format('Y-m-d');
    }
    
    /**
     * Calculate remaining days until deadline
     */
    public static function getRemainingDays($deadline_date) {
        if (!$deadline_date) {
            return null;
        }
        
        $deadline = new DateTime($deadline_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Reset time to start of day
        
        $interval = $today->diff($deadline);
        $remaining_days = $interval->days;
        
        // If deadline has passed, return negative number
        if ($today > $deadline) {
            return -$remaining_days;
        }
        
        return $remaining_days;
    }
    
    /**
     * Get deadline status
     */
    public static function getDeadlineStatus($deadline_date, $case_status = null) {
        if (!$deadline_date) {
            return 'no_deadline';
        }
        
        // If case is closed, deadline is no longer active
        if ($case_status === 'closed') {
            return 'closed';
        }
        
        $remaining_days = self::getRemainingDays($deadline_date);
        
        // Get settings from database (with fallback to default values)
        $deadline_urgent_days = 2;
        $deadline_warning_days = 5;
        
        if (function_exists('getSetting')) {
            $deadline_urgent_days = (int)getSetting('deadline_urgent_days', 2);
            $deadline_warning_days = (int)getSetting('deadline_warning_days', 5);
        }
        
        if ($remaining_days < 0) {
            return 'expired';
        } elseif ($remaining_days <= $deadline_urgent_days) {
            return 'urgent';
        } elseif ($remaining_days <= $deadline_warning_days) {
            return 'warning';
        } else {
            return 'normal';
        }
    }
    
    /**
     * Get deadline status text in Persian
     */
    public static function getDeadlineStatusText($deadline_date, $case_status = null) {
        $status = self::getDeadlineStatus($deadline_date, $case_status);
        
        switch ($status) {
            case 'closed':
                return 'بسته شده';
            case 'expired':
                return 'منقضی شده';
            case 'urgent':
                return 'فوری';
            case 'warning':
                return 'هشدار';
            case 'normal':
                return 'عادی';
            default:
                return 'بدون مهلت';
        }
    }
    
    /**
     * Get deadline status CSS class
     */
    public static function getDeadlineStatusClass($deadline_date, $case_status = null) {
        $status = self::getDeadlineStatus($deadline_date, $case_status);
        
        switch ($status) {
            case 'closed':
                return 'bg-dark';
            case 'expired':
                return 'bg-danger';
            case 'urgent':
                return 'bg-danger';
            case 'warning':
                return 'bg-warning';
            case 'normal':
                return 'bg-success';
            default:
                return 'bg-secondary';
        }
    }
    
    /**
     * Format deadline date in Jalali
     */
    public static function formatDeadlineDate($deadline_date) {
        if (!$deadline_date) {
            return 'تعیین نشده';
        }
        
        require_once 'JalaliDate.php';
        return JalaliDate::formatJalaliDate($deadline_date);
    }
    
    /**
     * Get deadline summary text
     */
    public static function getDeadlineSummary($deadline_date, $deadline_days = null, $case_status = null) {
        if (!$deadline_date) {
            return 'بدون مهلت';
        }
        
        $status_text = self::getDeadlineStatusText($deadline_date, $case_status);
        
        // If case is closed, don't show remaining days
        if ($case_status === 'closed') {
            return $status_text;
        }
        
        $remaining_days = self::getRemainingDays($deadline_date);
        
        // If deadline has expired, only show "منقضی شده" without days passed
        if ($remaining_days < 0) {
            return $status_text;
        } elseif ($remaining_days == 0) {
            return $status_text;
        } else {
            return $status_text . ' (' . $remaining_days . ' روز باقی‌مانده)';
        }
    }
}
?>
