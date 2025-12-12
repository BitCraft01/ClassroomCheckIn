<?php
// config.php - Database Configuration
session_start();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'mysql');
define('DB_NAME', 'classroom_checkin');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Helper function to validate email
function validate_email($email) {
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    return preg_match($pattern, $email);
}

// Helper function to validate password strength
function validate_password($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
    return preg_match($pattern, $password);
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to check user type
function is_instructor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'instructor';
}

function is_student() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>