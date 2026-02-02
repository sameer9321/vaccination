<?php
session_start();

$pageTitle = "Parent Dashboard";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parent id if missing */
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

/* Quick stats */
$totalChildren = 0;
$totalUpcoming = 0;
$totalPending = 0;

$stmtS = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM children WHERE parent_id = ?");
if ($stmtS) {
    mysqli_stmt_bind_param($stmtS, "i", $parentId);
    mysqli_stmt_execute($stmtS);
    $resS = mysqli_stmt_get_result($stmtS);
    $rowS = $resS ? mysqli_fetch_assoc($resS) : null;
    if ($rowS) $totalChildren = (int)($rowS["total"] ?? 0);
    mysqli_stmt_close($stmtS);
}

$stmtU1 = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE c.parent_id = ?
      AND b.booking_date >= CURDATE()
");
if ($stmtU1) {
    mysqli_stmt_bind_param($stmtU1, "i", $parentId);
    mysqli_stmt_execute($stmtU1);
    $resU1 = mysqli_stmt_get_result($stmtU1);
    $rowU1 = $resU1 ? mysqli_fetch_assoc($resU1) : null;
    if ($rowU1) $totalUpcoming = (int)($rowU1["total"] ?? 0);
    mysqli_stmt_close($stmtU1);
}

$stmtP1 = mysqli_prepare($conn, "
    SELECT COUNT(*) AS total
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE c.parent_id = ?
      AND LOWER(IFNULL(b.status,'')) = 'pending'
");
if ($stmtP1) {
    mysqli_stmt_bind_param($stmtP1, "i", $parentId);
    mysqli_stmt_execute($stmtP1);
    $resP1 = mysqli_stmt_get_result($stmtP1);
    $rowP1 = $resP1 ? mysqli_fetch_assoc($resP1) : null;
    if ($rowP1) $totalPending = (int)($rowP1["total"] ?? 0);
    mysqli_stmt_close($stmtP1);
}

/* Notifications */
$upcoming = [];
$recentUpdates = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        h.hospital_name,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    LEFT JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ?
      AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC
    LIMIT 6
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $parentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $upcoming[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$stmt2 = mysqli_prepare($conn, "
    SELECT
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        h.hospital_name,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    LEFT JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ?
      AND (
          LOWER(IFNULL(b.status,'')) IN ('done','completed','vaccinated')
          OR b.booking_date < CURDATE()
      )
    ORDER BY b.booking_date DESC
    LIMIT 6
");
if ($stmt2) {
    mysqli_stmt_bind_param($stmt2, "i", $parentId);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $recentUpdates[] = $row2;
    }
    mysqli_stmt_close($stmt2);
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
    min-width:110px;
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
.groupTitle{
    font-weight:700;
    margin:0 0 10px 0;
}
.list-group-item{
    border-color:#eef0f5;
}
.smallMeta{
    font-size:12px;
    color:#6c757d;
    margin-top:4px;
}
</style>

<div class="container-fluid">

    <div class="block-header">
        <ul class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a>
            </li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ul>
    </div>

    <!-- <div class="topCard">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <h3 class="m-0">Welcome, <?= htmlspecialchars($_SESSION["username"] ?? "Parent") ?></h3>
                <div class="text-muted" style="margin-top:6px;">
                    Manage children, bookings, requests, and reports
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalChildren ?></p>
                    <p class="statLbl">Children</p>
                </div>
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalUpcoming ?></p>
                    <p class="statLbl">Upcoming</p>
                </div>
                <div class="statPill">
                    <p class="statNum"><?= (int)$totalPending ?></p>
                    <p class="statLbl">Pending</p>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Boxes only -->
    <div class="row clearfix mb-2">

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-user fa-lg"></i></div>
                    <div class="featureTitle">Child Details</div>
                    <div class="featureText">Add, edit, and manage child records</div>
                    <a href="childDetails.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-calendar fa-lg"></i></div>
                    <div class="featureTitle">Vaccination Dates</div>
                    <div class="featureText">View upcoming vaccination schedules</div>
                    <a href="vaccinationDates.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-hospital-o fa-lg"></i></div>
                    <div class="featureTitle">Book Hospital</div>
                    <div class="featureText">Create booking by date and hospital</div>
                    <a href="bookHospital.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-file-text fa-lg"></i></div>
                    <div class="featureTitle">Vaccination Report</div>
                    <div class="featureText">View history and download reports</div>
                    <a href="vaccinationReport.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card featureCard">
                <div class="featureBody">
                    <div class="iconCircle"><i class="fa fa-plus-square fa-lg"></i></div>
                    <div class="featureTitle">Request Hospital</div>
                    <div class="featureText">Request a hospital that is not listed</div>
                    <a href="requestHospital.php" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

    </div>

    <!-- Notifications on separate line -->
    <div class="row clearfix">
        <div class="col-lg-12 mb-3">
            <div class="notifyCard">
                <div class="notifyHead">
                    <div style="font-weight:700; font-size:16px;">Notifications</div>
                    <span class="miniBadge"><?= (int)(count($upcoming) + count($recentUpdates)) ?></span>
                </div>

                <div class="notifyBody">
                    <div class="row">

                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="groupTitle">Upcoming Vaccinations</div>
                            <ul class="list-group">
                                <?php if (!empty($upcoming)): ?>
                                    <?php foreach ($upcoming as $n): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($n["child_name"] ?? "") ?></strong>
                                            (<?= htmlspecialchars($n["vaccine_name"] ?? "") ?>)
                                            <div class="smallMeta">
                                                <?= htmlspecialchars($n["booking_date"] ?? "") ?>
                                                <?php if (!empty($n["hospital_name"])): ?>
                                                    , <?= htmlspecialchars($n["hospital_name"]) ?>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">No upcoming vaccinations</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <div class="groupTitle">Recent Updates</div>
                            <ul class="list-group">
                                <?php if (!empty($recentUpdates)): ?>
                                    <?php foreach ($recentUpdates as $r): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($r["child_name"] ?? "") ?></strong>
                                            (<?= htmlspecialchars($r["vaccine_name"] ?? "") ?>)
                                            <div class="smallMeta">
                                                <?= htmlspecialchars($r["booking_date"] ?? "") ?>, Status: <?= htmlspecialchars($r["status"] ?? "") ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">No recent updates</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include "../base/footer.php"; ?>
