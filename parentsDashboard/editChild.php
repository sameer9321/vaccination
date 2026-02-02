<?php
session_start();

$pageTitle = "Edit Child";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");

/* children.parent_id references parents.parent_id */
$parentId = (int)($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $userEmail = "";

    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = $resU ? mysqli_fetch_assoc($resU) : null;
        mysqli_stmt_close($stmtU);

        if ($rowU && isset($rowU["email"])) {
            $userEmail = (string)$rowU["email"];
        }
    }

    if ($userEmail !== "") {
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        if ($stmtP) {
            mysqli_stmt_bind_param($stmtP, "s", $userEmail);
            mysqli_stmt_execute($stmtP);
            $resP = mysqli_stmt_get_result($stmtP);
            $rowP = $resP ? mysqli_fetch_assoc($resP) : null;
            mysqli_stmt_close($stmtP);

            if ($rowP && isset($rowP["parent_id"])) {
                $parentId = (int)$rowP["parent_id"];
            }
        }

        if ($parentId <= 0) {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, ?)");
            if ($stmtIns) {
                $blankPass = "";
                mysqli_stmt_bind_param($stmtIns, "sss", $username, $userEmail, $blankPass);
                mysqli_stmt_execute($stmtIns);
                $parentId = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmtIns);
            }
        }
    }

    if ($parentId > 0) {
        $_SESSION["parent_id"] = $parentId;
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please log out and log in again.");
}

$childId = (int)($_GET["id"] ?? 0);
if ($childId <= 0) {
    header("Location: childDetails.php");
    exit;
}

/* Fetch child, only if it belongs to this parent */
$child = null;
$stmt = mysqli_prepare($conn, "
    SELECT child_id, child_name, birth_date, vaccination_status
    FROM children
    WHERE child_id = ? AND parent_id = ?
    LIMIT 1
");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "ii", $childId, $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$child = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$child) {
    die("Child not found or you do not have access.");
}

/* Update child */
if (isset($_POST["save_child"])) {
    $childName = trim($_POST["child_name"] ?? "");
    $birthDate = trim($_POST["birth_date"] ?? "");
    $vaccStatus = trim($_POST["vaccination_status"] ?? "Pending");

    if ($childName === "" || $birthDate === "") {
        header("Location: editChild.php?id=" . $childId . "&error=1");
        exit;
    }

    $stmtUp = mysqli_prepare($conn, "
        UPDATE children
        SET child_name = ?, birth_date = ?, vaccination_status = ?
        WHERE child_id = ? AND parent_id = ?
    ");
    if (!$stmtUp) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmtUp, "sssii", $childName, $birthDate, $vaccStatus, $childId, $parentId);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    header("Location: childDetails.php?updated=1");
    exit;
}

include "../base/header.php";
?>

<style>
.cardBox{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.formBox{
    border-radius:12px;
    border:1px solid #eef0f5;
    padding:18px;
    background:#fff;
}
</style>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
            <h3 style="margin:0;">Edit Child</h3>
            <?php if (isset($_GET["error"])): ?>
                <div style="color:#b00020; margin-top:6px;">Please fill required fields.</div>
            <?php endif; ?>
        </div>
        <a href="childDetails.php" class="btn btn-outline-primary" style="padding:8px 12px;">Back</a>
    </div>

    <div class="cardBox">
        <div class="formBox">
            <form method="post">
                <div style="margin-bottom:12px;">
                    <label style="display:block; margin-bottom:6px;">Child Name</label>
                    <input type="text" name="child_name" class="form-control" required value="<?= htmlspecialchars($child["child_name"] ?? "") ?>">
                </div>

                <div style="margin-bottom:12px;">
                    <label style="display:block; margin-bottom:6px;">Birth Date</label>
                    <input type="date" name="birth_date" class="form-control" required value="<?= htmlspecialchars($child["birth_date"] ?? "") ?>">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; margin-bottom:6px;">Vaccination Status</label>
                    <?php $vs = (string)($child["vaccination_status"] ?? "Pending"); ?>
                    <select name="vaccination_status" class="form-control">
                        <option value="Pending" <?= $vs === "Pending" ? "selected" : "" ?>>Pending</option>
                        <option value="Up to date" <?= $vs === "Up to date" ? "selected" : "" ?>>Up to date</option>
                        <option value="Completed" <?= $vs === "Completed" ? "selected" : "" ?>>Completed</option>
                    </select>
                </div>

                <button type="submit" name="save_child" class="btn btn-primary" style="padding:10px 14px;">
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
