<?php
/**
 * Session Helper Class
 * مدیریت session ها
 */

class SessionHelper {
    
    /**
     * Start session if not already started
     */
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if session variable exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::start();
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::get('logged_in', false);
    }
    
    /**
     * Set user session data
     */
    public static function setUser($user_id, $username, $role, $display_name = null) {
        self::set('user_id', $user_id);
        self::set('username', $username);
        self::set('role', $role);
        self::set('display_name', $display_name);
        self::set('logged_in', true);
    }
    
    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => self::get('user_id'),
            'username' => self::get('username'),
            'role' => self::get('role'),
            'display_name' => self::get('display_name')
        ];
    }
    
    /**
     * Clear user session
     */
    public static function clearUser() {
        self::remove('user_id');
        self::remove('username');
        self::remove('role');
        self::remove('display_name');
        self::remove('logged_in');
    }
}
?>
