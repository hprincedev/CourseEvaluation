<?php
/**
 * Database Connection File using PHP PDO
 * Course Evaluation Management System (CEMsystem)
 */

$host     = 'localhost';
$dbname   = 'course_evaluation';
$username = 'root';
$password = ''; // Default XAMPP password is empty
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // For beginners: clean and informative error message
    die("Database connection failed: " . $e->getMessage());
}
?>
