<?php
session_start();

$pageTitle = "Hospital Dashboard";
include "../includes/db.php";

/* Auth check */
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

/*
  Resolve hospital_id if missing
  We only use columns that exist:
  - hospitals.email
  - hospitals.hospital_name
*/
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
    if ($hospitalId <= 0 && $userEmail !== "") {
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

    /* Fallback match by hospital_name = username */
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
    die("Hospital account not linked. Please make sure the hospital email exists in hospitals table and matches users.email.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "";
    try {
        $dob = new DateTime($birthDate);
        $now = new DateTime();
        $diff = $now->diff($dob);

        $y = (int)$diff->y;
        $m = (int)$diff->m;
        $d = (int)$diff->d;

        if ($y > 0) return $y . " yrs";
        if ($m > 0) return $m . " months";
        return $d . " days";
    } catch (Exception $e) {
        return "";
    }
}

/* Stats */
$totalAppointments = 0;
$totalPending = 0;
$totalVaccinated = 0;

$stmtS = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM bookings WHERE hospital_id = ?");
if ($stmtS) {
    mysqli_stmt_bind_param($stmtS, "i", $hospitalId);
    mysqli_stmt_execute($stmtS);
    $resS = mysqli_stmt_get_result($stmtS);
    $rowS = $resS ? mysqli_fetch_assoc($resS) : null;
    if ($rowS) $totalAppointments = (int)($rowS["total"] ?? 0);
    mysqli_stmt_close($stmtS);
}

$stmtSP = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM bookings WHERE hospital_id = ? AND LOWER(IFNULL(status,'')) = 'pending'");
if ($stmtSP) {
    mysqli_stmt_bind_param($stmtSP, "i", $hospitalId);
    mysqli_stmt_execute($stmtSP);
    $resSP = mysqli_stmt_get_result($stmtSP);
    $rowSP = $resSP ? mysqli_fetch_assoc($resSP) : null;
    if ($rowSP) $totalPending = (int)($rowSP["total"] ?? 0);
    mysqli_stmt_close($stmtSP);
}

$stmtSV = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM bookings WHERE hospital_id = ? AND LOWER(IFNULL(status,'')) IN ('vaccinated','done','completed')");
if ($stmtSV) {
    mysqli_stmt_bind_param($stmtSV, "i", $hospitalId);
    mysqli_stmt_execute($stmtSV);
    $resSV = mysqli_stmt_get_result($stmtSV);
    $rowSV = $resSV ? mysqli_fetch_assoc($resSV) : null;
    if ($rowSV) $totalVaccinated = (int)($rowSV["total"] ?? 0);
    mysqli_stmt_close($stmtSV);
}

/* Upcoming appointments */
$appointments = [];

$stmtA = mysqli_prepare($conn, "
    SELECT
        b.id AS booking_id,
        c.child_name,
        c.birth_date,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.hospital_id = ?
      AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC
    LIMIT 10
");
if ($stmtA) {
    mysqli_stmt_bind_param($stmtA, "i", $hospitalId);
    mysqli_stmt_execute($stmtA);
    $resA = mysqli_stmt_get_result($stmtA);
    while ($rowA = mysqli_fetch_assoc($resA)) {
        $appointments[] = $rowA;
    }
    mysqli_stmt_close($stmtA);
}

include "../base/header.php";
?>

<style>
.topCard{
    border-radius:16px;
    padding:18px;
    background:#fff;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
    margin-bottom:16px;
}
.statPill{
    border-radius:14px;
    padding:12px 14px;
    background:#f5f6fa;
    min-width:120px;
    text-align:center;
}
.statNum{
    font-size:22px;
    font-weight:700;
    margin:0;
}
.statLbl{
    font-size:12px;
    color:#6c757d;
    margin:0;
}
.featureCard{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
    overflow:hidden;
    transition:transform 0.18s ease, box-shadow 0.18s ease;
    height:100%;
}
.featureCard:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 26px rgba(0,0,0,0.10);
}
.featureBody{
    padding:18px;
    text-align:center;
}
.iconCircle{
    width:52px;
    height:52px;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 10px auto;
    background:#f5f6fa;
}
.featureTitle{
    margin:0 0 6px 0;
    font-weight:700;
}
.featureText{
    margin:0 0 12px 0;
    color:#6c757d;
    font-size:13px;
    min-height:38px;
}
.notifyCard{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
    overflow:hidden;
}
.notifyHead{
    padding:14px 18px;
    border-bottom:1px solid #eef0f5;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.notifyBody{
    padding:18px;
}
.miniBadge{
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    background:#0d6efd;
    color:#fff;
}
.table thead th{
    background:#f5f6fa;
}
.pill{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
}
.pillPending{ background:#fff3cd; color:#856404; }
.pillDone{ background:#d4edda; color:#155724; }
.pillOther{ background:#e2e3e5; color:#383d41; }
</style>

<div class="container-fluid">

    <div class="block-header">
        <ul class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="hospitalDashboard.php"><i class="fa fa-dashboard"></i></a>
            </li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ul>
    </div>

    <div class="topCard">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <h3 class="m-0">Welcome, <?= htmlspecialchars($_SESSION["hospital_name"] ?? $_SESSION["username"] ?? "Hospital") ?></h3>
                <div class="text-muted" style="margin-top:6px;">
                    View appointments and update vaccination status
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalAppointments ?></p>
                    <p class="statLbl">Appointments</p>
                </div>
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalPending ?></p>
                    <p class="statLbl">Pending</p>
                </div>
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalVaccinated ?></p>
                    <p class="statLbl">Vaccinated</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Boxes -->
    <div class="row clearfix mb-2">

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-pencil-square-o fa-lg"></i></div>
                    <div class="featureTitle">Update Vaccine Status</div>
                    <div class="featureText">Update status as Vaccinated or Not Vaccinated</div>
                    <a href="appointments.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-calendar fa-lg"></i></div>
                    <div class="featureTitle">Appointments</div>
                    <div class="featureText">View all upcoming appointments</div>
                    <a href="appointments.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

    </div>

    <!-- Upcoming Appointments -->
    <div class="row clearfix">
        <div class="col-lg-12 mb-3">
            <div class="notifyCard">
                <div class="notifyHead">
                    <div style="font-weight:700; font-size:16px;">Upcoming Appointments</div>
                    <span class="miniBadge"><?= (int)count($appointments) ?></span>
                </div>

                <div class="notifyBody">
                    <div style="overflow:auto;">
                        <table class="table table-striped table-bordered text-center align-middle" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Age</th>
                                    <th>Vaccine</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th style="width:140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($appointments) > 0): ?>
                                    <?php foreach ($appointments as $row): ?>
                                        <?php
                                            $st = strtolower((string)($row["status"] ?? ""));
                                            $cls = "pillOther";
                                            if ($st === "pending") $cls = "pillPending";
                                            if (in_array($st, ["vaccinated","done","completed"], true)) $cls = "pillDone";
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row["child_name"] ?? "") ?></td>
                                            <td><?= htmlspecialchars(calc_age_text($row["birth_date"] ?? "")) ?></td>
                                            <td><?= htmlspecialchars($row["vaccine_name"] ?? "") ?></td>
                                            <td><?= htmlspecialchars($row["booking_date"] ?? "") ?></td>
                                            <td><span class="pill <?= $cls ?>"><?= htmlspecialchars($row["status"] ?? "Pending") ?></span></td>
                                            <td>
                                                <a href="appointments.php?focus=<?= (int)($row["booking_id"] ?? 0) ?>"
                                                   class="btn btn-success btn-sm">
                                                    Update
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include "../base/footer.php"; ?>
