<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------
// RDS MySQL Configuration
// ----------------------------
$host = "bnd-db.clkymsu642nu.ap-southeast-1.rds.amazonaws.com"; // Your RDS endpoint
$dbname = "bank_db";       // Your database name
$username = "admin";       // Your RDS username
$password = "admin123";    // Your RDS password

// ----------------------------
// Create MySQLi connection
// ----------------------------
$mysqli = new mysqli($host, $username, $password, $dbname);

// ----------------------------
// Check connection
// ----------------------------
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Optional: Set charset to utf8mb4 for proper encoding
$mysqli->set_charset("utf8mb4");

// ----------------------------
// Function to sanitize inputs (optional but recommended)
// ----------------------------
function sanitize_input($data) {
    global $mysqli;
    return htmlspecialchars($mysqli->real_escape_string(trim($data)));
}
?>
