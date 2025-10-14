<?php
/**
 * AuditLogger
 * Simple audit logging utility to track user actions
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/SessionHelper.php';

class AuditLogger {
    private static $initialized = false;

    private static function initTable($conn) {
        if (self::$initialized) return;
        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            username VARCHAR(100) NULL,
            role VARCHAR(50) NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NULL,
            entity_id VARCHAR(100) NULL,
            details JSON NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        try {
            $conn->exec($sql);
            self::$initialized = true;
        } catch (Exception $e) {
            // fail silently; logging should never break main flow
            error_log('Audit table init failed: ' . $e->getMessage());
        }
    }

    public static function log($action, $entityType = null, $entityId = null, $details = []) {
        try {
            global $pdo;
            $conn = $pdo;
            if (!$conn) return;

            self::initTable($conn);

            $user = SessionHelper::getCurrentUser();
            $userId = $user['id'] ?? null;
            $username = $user['username'] ?? null;
            $role = $user['role'] ?? null;

            // Determine client IP (consider proxies)
            $ip = null;
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                // Can be a comma-separated list. Take the first non-empty
                $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($parts[0]);
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $query = "INSERT INTO audit_logs (user_id, username, role, action, entity_type, entity_id, details, ip_address, user_agent)
                      VALUES (:user_id, :username, :role, :action, :entity_type, :entity_id, :details, :ip, :ua)";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':entity_type', $entityType);
            $stmt->bindValue(':entity_id', $entityId);
            $stmt->bindValue(':details', !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':ua', $ua);
            $stmt->execute();
        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
