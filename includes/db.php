<?php
// db.php - Database connection for vaccination portal

$host = "localhost";      // XAMPP default
$db   = "vaccination_portal"; // Your database name
$user = "root";           // XAMPP default
$pass = "";               // XAMPP default password is empty

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set character set
$conn->set_charset("utf8");
?>
