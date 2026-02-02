<?php
session_start();
include "includes/db.php";

$username = trim($_POST["username"] ?? "");
$password = (string)($_POST["password"] ?? "");
$email    = trim($_POST["email"] ?? "");
$role     = strtolower(trim($_POST["role"] ?? ""));
$action   = trim($_POST["action"] ?? "");

function getRedirectPath($role) {
    if ($role === "parent")   return "parentsDashboard/parentdashboard.php";
    if ($role === "hospital") return "hospitalDashboard/hospitalDashboard.php";
    if ($role === "admin")    return "mainadmin/index.php";
    return "mainadmin/index.php";
}

$response = ["status" => false, "msg" => "Invalid request"];

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

        // Set base session
        $_SESSION["role"] = $role;
        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["username"] = (string)$user["username"];

        // Parent linking (optional)
        if ($role === "parent") {
            $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
            if ($stmtP) {
                mysqli_stmt_bind_param($stmtP, "s", $user["email"]);
                mysqli_stmt_execute($stmtP);
                $resP = mysqli_stmt_get_result($stmtP);
                $rowP = $resP ? mysqli_fetch_assoc($resP) : null;
                mysqli_stmt_close($stmtP);

                if ($rowP && isset($rowP["parent_id"])) {
                    $_SESSION["parent_id"] = (int)$rowP["parent_id"];
                }
            }
        }

        // Hospital linking (IMPORTANT)
        if ($role === "hospital") {
            $hid = 0;
            $hname = "";

            // Match hospital by email
            $stmtH = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE email = ? LIMIT 1");
            if ($stmtH) {
                mysqli_stmt_bind_param($stmtH, "s", $user["email"]);
                mysqli_stmt_execute($stmtH);
                $resH = mysqli_stmt_get_result($stmtH);
                $rowH = $resH ? mysqli_fetch_assoc($resH) : null;
                mysqli_stmt_close($stmtH);

                if ($rowH && isset($rowH["id"])) {
                    $hid = (int)$rowH["id"];
                    $hname = (string)($rowH["hospital_name"] ?? "");
                }
            }

            // Fallback: match by hospital_name = username
            if ($hid <= 0) {
                $stmtH2 = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE hospital_name = ? LIMIT 1");
                if ($stmtH2) {
                    mysqli_stmt_bind_param($stmtH2, "s", $user["username"]);
                    mysqli_stmt_execute($stmtH2);
                    $resH2 = mysqli_stmt_get_result($stmtH2);
                    $rowH2 = $resH2 ? mysqli_fetch_assoc($resH2) : null;
                    mysqli_stmt_close($stmtH2);

                    if ($rowH2 && isset($rowH2["id"])) {
                        $hid = (int)$rowH2["id"];
                        $hname = (string)($rowH2["hospital_name"] ?? "");
                    }
                }
            }

            if ($hid > 0) {
                $_SESSION["hospital_id"] = $hid;
                $_SESSION["hospital_name"] = $hname;
            }
        }

        $response = [
            "status" => true,
            "msg" => "Login successful",
            "redirect" => getRedirectPath($role)
        ];
    }
}

if ($action === "register") {

    if ($username === "" || $password === "" || $email === "" || $role === "") {
        $response["msg"] = "Please fill all fields";
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users(username, email, password, role) VALUES(?,?,?,?)");
    if (!$stmt) {
        $response["msg"] = "DB error: " . mysqli_error($conn);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $role);

    if (!mysqli_stmt_execute($stmt)) {
        $response["msg"] = "Registration failed: " . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    } else {
        mysqli_stmt_close($stmt);

        $_SESSION["role"] = $role;
        $_SESSION["username"] = $username;
        $_SESSION["user_id"] = (int)mysqli_insert_id($conn);

        // IMPORTANT: if hospital registers, also insert into hospitals table
        if ($role === "hospital") {
            $stmtInsH = mysqli_prepare($conn, "INSERT INTO hospitals (hospital_name, address, phone, email) VALUES (?, ?, ?, ?)");
            if ($stmtInsH) {
                $blankAddress = "";
                $blankPhone = "";
                mysqli_stmt_bind_param($stmtInsH, "ssss", $username, $blankAddress, $blankPhone, $email);
                mysqli_stmt_execute($stmtInsH);
                $newHid = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmtInsH);

                if ($newHid > 0) {
                    $_SESSION["hospital_id"] = $newHid;
                    $_SESSION["hospital_name"] = $username;
                }
            }
        }

        $response = [
            "status" => true,
            "msg" => "Registered successfully",
            "redirect" => getRedirectPath($role)
        ];
    }
}

header("Content-Type: application/json");
echo json_encode($response);
exit;
