<?php
session_start();
include 'includes/db.php'; // Ensure this file has your $conn

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email    = $_POST['email'] ?? '';
$role     = strtolower($_POST['role'] ?? '');
$action   = $_POST['action'] ?? '';

$response = ["status" => false, "msg" => "Execution started"];

// This function maps the role to your specific files
function getRedirectPath($role) {
    switch ($role) {
        case 'parent':
            return "parentsDashboard\parentdashboard.php";
        case 'hospital':
            return "hospitalDashboard\hospitalDashboard.php";
        case 'admin':
            return "mainadmin\index.php";
        default:
            return "mainadmin\index.php";
         
    }
}

if ($action == "login") {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND role=?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['role'] = $role;
            $_SESSION['username'] = $username;
            
            $response = [
                "status" => true,
                "msg" => "Login successful!",
                "redirect" => getRedirectPath($role)
            ];
        } else {
            $response["msg"] = "Wrong password";
        }
    } else {
        $response["msg"] = "User not found for role: " . $role;
    }
}

if ($action == "register") {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users(username, email, password, role) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss", $username, $email, $hash, $role);

    if ($stmt->execute()) {
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $username;
        $response = [
            "status" => true,
            "msg" => "Registered successfully!",
            "redirect" => getRedirectPath($role)
        ];
    } else {
        $response["msg"] = "Registration failed: " . $conn->error;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;