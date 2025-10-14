<?php
/**
 * Centralized Header for Protected Pages
 * Handles session management and includes necessary files
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'db.php';
require_once 'functions.php';

// Check SSL redirect if enabled
checkSSLRedirect();

// Check if user is logged in, redirect to login if not
requireLogin();
?>
