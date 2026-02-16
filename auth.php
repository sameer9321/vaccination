<?php
session_start();
include "includes/db.php";

$username = trim($_POST["username"] ?? "");
$password = (string)($_POST["password"] ?? "");
$email    = trim($_POST["email"] ?? "");
$phone    = trim($_POST["phone"] ?? "");
$address  = trim($_POST["address"] ?? "");
$role     = strtolower(trim($_POST["role"] ?? ""));
$action   = trim($_POST["action"] ?? "");

function getRedirectPath($role) {
    if ($role === "parent")   return "parentsDashboard/parentdashboard.php";
    if ($role === "hospital") return "hospitalDashboard/hospitalDashboard.php";
    if ($role === "admin")    return "mainadmin/index.php";
    return "mainadmin/index.php";
}

$response = ["status" => false, "msg" => "Invalid request"];

// --- LOGIN LOGIC ---
if ($action === "login") {
    $stmt = mysqli_prepare($conn, "SELECT id, username, email, password, role FROM users WHERE username = ? AND role = ? LIMIT 1");
    if (!$stmt) {
        $response["msg"] = "DB error: " . mysqli_error($conn);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ss", $username, $role);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$user) {
        $response["msg"] = "User not found";
    } elseif (!password_verify($password, $user["password"])) {
        $response["msg"] = "Wrong password";
    } else {
        $_SESSION["role"] = $role;
        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["username"] = (string)$user["username"];

        if ($role === "parent") {
            $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
            if ($stmtP) {
                mysqli_stmt_bind_param($stmtP, "s", $user["email"]);
                mysqli_stmt_execute($stmtP);
                $resP = mysqli_stmt_get_result($stmtP);
                $rowP = $resP ? mysqli_fetch_assoc($resP) : null;
                mysqli_stmt_close($stmtP);
                if ($rowP) $_SESSION["parent_id"] = (int)$rowP["parent_id"];
            }
        }

        if ($role === "hospital") {
            $stmtH = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE email = ? LIMIT 1");
            if ($stmtH) {
                mysqli_stmt_bind_param($stmtH, "s", $user["email"]);
                mysqli_stmt_execute($stmtH);
                $resH = mysqli_stmt_get_result($stmtH);
                $rowH = $resH ? mysqli_fetch_assoc($resH) : null;
                mysqli_stmt_close($stmtH);
                if ($rowH) {
                    $_SESSION["hospital_id"] = (int)$rowH["id"];
                    $_SESSION["hospital_name"] = $rowH["hospital_name"];
                }
            }
        }

        $response = [
            "status" => true,
            "msg" => "Login successful",
            "redirect" => getRedirectPath($role)
        ];
    }
}

// --- REGISTER LOGIC ---
if ($action === "register") {
    if ($role === "admin") {
        $response["msg"] = "Admin registration is not allowed.";
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    // Hospital requires phone + address too
    if ($role === "hospital") {
        if ($username === "" || $password === "" || $email === "" || $phone === "" || $address === "") {
            $response["msg"] = "Please fill all fields";
            header("Content-Type: application/json");
            echo json_encode($response);
            exit;
        }
    } else {
        if ($username === "" || $password === "" || $email === "" || $role === "") {
            $response["msg"] = "Please fill all fields";
            header("Content-Type: application/json");
            echo json_encode($response);
            exit;
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users(username, email, password, role) VALUES(?,?,?,?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $role);

        if (mysqli_stmt_execute($stmt)) {
            $newUserId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            if ($role === "hospital") {
                // Store the new hospital details
                $stmtInsH = mysqli_prepare(
                    $conn,
                    "INSERT INTO hospitals (hospital_name, address, phone, email) VALUES (?, ?, ?, ?)"
                );
                if ($stmtInsH) {
                    mysqli_stmt_bind_param($stmtInsH, "ssss", $username, $address, $phone, $email);
                    mysqli_stmt_execute($stmtInsH);
                    mysqli_stmt_close($stmtInsH);
                }
            }

            $response = [
                "status" => true,
                "msg" => "Registration successful! Please login to continue."
            ];
        } else {
            $response["msg"] = "Registration failed (Username/Email might exist)";
        }
    } else {
        $response["msg"] = "DB error: " . mysqli_error($conn);
    }
}

header("Content-Type: application/json");
echo json_encode($response);
exit;
