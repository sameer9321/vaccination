<?php
session_start();

$pageTitle = "Hospital Profile";
include "../includes/db.php";

/* Hospital auth */
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

/* Resolve hospital id if missing */
if ($hospitalId <= 0 && $userId > 0) {

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

    /* Try match by email */
    if ($userEmail !== "") {
        $stmtH = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE email = ? LIMIT 1");
        if ($stmtH) {
            mysqli_stmt_bind_param($stmtH, "s", $userEmail);
            mysqli_stmt_execute($stmtH);
            $resH = mysqli_stmt_get_result($stmtH);
            $rowH = $resH ? mysqli_fetch_assoc($resH) : null;
            mysqli_stmt_close($stmtH);

            if ($rowH && isset($rowH["id"])) {
                $hospitalId = (int)$rowH["id"];
                $_SESSION["hospital_id"] = $hospitalId;
                $_SESSION["hospital_name"] = (string)($rowH["hospital_name"] ?? "");
            }
        }
    }

    /* Fallback match by hospital_name equals username */
    if ($hospitalId <= 0 && $username !== "") {
        $stmtH2 = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE hospital_name = ? LIMIT 1");
        if ($stmtH2) {
            mysqli_stmt_bind_param($stmtH2, "s", $username);
            mysqli_stmt_execute($stmtH2);
            $resH2 = mysqli_stmt_get_result($stmtH2);
            $rowH2 = $resH2 ? mysqli_fetch_assoc($resH2) : null;
            mysqli_stmt_close($stmtH2);

            if ($rowH2 && isset($rowH2["id"])) {
                $hospitalId = (int)$rowH2["id"];
                $_SESSION["hospital_id"] = $hospitalId;
                $_SESSION["hospital_name"] = (string)($rowH2["hospital_name"] ?? "");
            }
        }
    }
}

if ($hospitalId <= 0) {
    die("Hospital not linked. Please log out and log in again.");
}

/* Update profile */
if (isset($_POST["save_profile"])) {

    $hospitalName = trim((string)($_POST["hospital_name"] ?? ""));
    $address = trim((string)($_POST["address"] ?? ""));
    $phone = trim((string)($_POST["phone"] ?? ""));

    if ($hospitalName === "" || $address === "" || $phone === "") {
        header("Location: profile.php?error=1");
        exit;
    }

    $stmtUp = mysqli_prepare(
        $conn,
        "UPDATE hospitals SET hospital_name = ?, address = ?, phone = ? WHERE id = ?"
    );

    if (!$stmtUp) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtUp, "sssi", $hospitalName, $address, $phone, $hospitalId);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    $_SESSION["hospital_name"] = $hospitalName;

    header("Location: profile.php?saved=1");
    exit;
}

/* Fetch current hospital data */
$hospital = [
    "hospital_name" => "",
    "address" => "",
    "phone" => "",
    "email" => ""
];

$stmt = mysqli_prepare($conn, "SELECT hospital_name, address, phone, email FROM hospitals WHERE id = ? LIMIT 1");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $hospitalId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if ($row) {
    $hospital["hospital_name"] = (string)($row["hospital_name"] ?? "");
    $hospital["address"] = (string)($row["address"] ?? "");
    $hospital["phone"] = (string)($row["phone"] ?? "");
    $hospital["email"] = (string)($row["email"] ?? "");
}

include "../base/header.php";
?>

<style>
.profileCard{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.headRow{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.note{
    color:#6c757d;
    margin:6px 0 0 0;
    font-size:13px;
}
.form-control{
    height:42px;
}
.badgeSoft{
    background:#0d6efd;
    color:#fff;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
}
</style>

<div class="container-fluid">

    <div class="profileCard">

        <div class="headRow">
            <div>
                <h4 class="m-0">Hospital Profile</h4>
                <p class="note">Update your hospital details. Email stays the same.</p>
            </div>
            <span class="badgeSoft">Hospital ID: <?= (int)$hospitalId ?></span>
        </div>

        <?php if (isset($_GET["saved"])): ?>
            <div class="alert alert-success mb-3">Profile updated successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-danger mb-3">Please fill all fields.</div>
        <?php endif; ?>

        <form method="post" class="row g-3">

            <div class="col-12 col-md-4">
                <label class="form-label mb-1">Hospital Name</label>
                <input
                    type="text"
                    name="hospital_name"
                    class="form-control"
                    value="<?= htmlspecialchars($hospital["hospital_name"], ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label mb-1">Address</label>
                <input
                    type="text"
                    name="address"
                    class="form-control"
                    value="<?= htmlspecialchars($hospital["address"], ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label mb-1">Phone</label>
                <input
                    type="text"
                    name="phone"
                    class="form-control"
                    value="<?= htmlspecialchars($hospital["phone"], ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label mb-1">Email</label>
                <input
                    type="email"
                    class="form-control"
                    value="<?= htmlspecialchars($hospital["email"], ENT_QUOTES, "UTF-8") ?>"
                    disabled
                >
                <div class="note">Email is linked with your login.</div>
            </div>

            <div class="col-12 d-grid">
                <button type="submit" name="save_profile" class="btn btn-success">
                    Save Changes
                </button>
            </div>

        </form>
    </div>

</div>

<?php include "../base/footer.php"; ?>
