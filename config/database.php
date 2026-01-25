<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u208951792_skoolydemouser');
define('DB_PASS', '!mO1cl=bKZ');
define('DB_NAME', 'u208951792_skooly_demo');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Site Configuration
define('SITE_NAME', 'Quolytech School Management');
define('SITE_URL', 'https://azure-moose-466393.hostingersite.com/');
define('ADMIN_EMAIL', 'admin@school.com');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Tirane');
?>
