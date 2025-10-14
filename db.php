<?php
/**
 * Database Connection File
 * Simple database connection for compatibility
 */

require_once 'config.php';

// Create a simple function to get database connection
function getDB() {
    global $pdo;
    return $pdo;
}

// For backward compatibility, you can also use this
$db = getDB();
?>
