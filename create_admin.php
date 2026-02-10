<?php
include "includes/db.php"; // Ensure this path is correct

$username = 'admin';
$password = 'admin123'; // Change this to your desired password
$email = 'admin@gmail.com';
$role = 'admin';

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare the SQL statement
$sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt->execute([$username, $hashed_password, $email, $role])) {
    echo "Admin account created successfully!";
} else {
    echo "Error creating account.";
}
?>