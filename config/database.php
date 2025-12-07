<?php
/**
 * Database Configuration
 * PDO connection setup with error handling
 */

// Correct constant names
define('DB_PORT', '3307');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '0920');
define('DB_NAME', 'lnf');

// Create PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}




// Database credentials
//define('DB_HOST', 'localhost');
//define('DB_PORT', '3307'); // Make sure this matches your XAMPP MySQL port
//define('DB_USER', 'root');
//define('DB_PASS', '0920');
//define('DB_NAME', 'lnf');
//
//try {
//    // Connect to the specific database with the correct port
//    $pdo = new PDO(
//        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
//        DB_USER,
//        DB_PASS,
//        [
//            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//            PDO::ATTR_EMULATE_PREPARES => false
//        ]
//    );
//
//} catch (PDOException $e) {
//    die("Database connection failed: " . $e->getMessage());
//}


//?>
