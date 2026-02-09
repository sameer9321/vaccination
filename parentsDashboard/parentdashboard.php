<?php
session_start();
$pageTitle = "Parent Dashboard";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "Parent");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $userRow = mysqli_fetch_assoc($resU);
    if ($userRow) {
        $email = $userRow['email'];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $email);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $pData = mysqli_fetch_assoc($resP);
        if ($pData) {
            $parentId = $pData['parent_id'];
        } else {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, '')");
            mysqli_stmt_bind_param($stmtIns, "ss", $username, $email);
            mysqli_stmt_execute($stmtIns);
            $parentId = mysqli_insert_id($conn);
        }
        $_SESSION["parent_id"] = $parentId;
    }
}

$totalChildren = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM children WHERE parent_id = $parentId"))['total'];
$totalUpcoming = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE()"))['total'];
$totalPending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospital_requests WHERE parent_id = $parentId AND status = 'Pending'"))['total'];
$totalCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.status IN ('Vaccinated', 'Completed')"))['total'];
// Missing query for the 5th card
$totalHospitals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospitals"))['total'];

$upcomingList = mysqli_query($conn, "SELECT c.child_name, b.vaccine_name, b.booking_date, b.status FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC LIMIT 4");

include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Welcome, <?= htmlspecialchars($username) ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card info-box-2 bg-blue">
                <div class="body">
                    <div class="icon"><i class="fa fa-child"></i></div>
                    <div class="content">
                        <div class="text">TOTAL CHILDREN</div>
                        <div class="number"><?= $totalChildren ?></div>
                    </div>
                </div>
                <a href="childDetails.php" class="card-footer text-white text-center d-block py-2" style="background: rgba(0,0,0,0.1); text-decoration:none;">
                    View Details <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card info-box-2 bg-green">
                <div class="body">
                    <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
                    <div class="content">
                        <div class="text">UPCOMING VACCINES</div>
                        <div class="number"><?= $totalUpcoming ?></div>
                    </div>
                </div>
                <a href="vaccinationDates.php" class="card-footer text-white text-center d-block py-2" style="background: rgba(0,0,0,0.1); text-decoration:none;">
                    View Schedule <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card info-box-2 bg-orange">
                <div class="body">
                    <div class="icon"><i class="fa fa-clock-o"></i></div>
                    <div class="content">
                        <div class="text">PENDING REQUESTS</div>
                        <div class="number"><?= $totalPending ?></div>
                    </div>
                </div>
                <a href="requestHospitals.php" class="card-footer text-white text-center d-block py-2" style="background: rgba(0,0,0,0.1); text-decoration:none;">
                    Track Requests <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card info-box-2 bg-purple">
                <div class="body">
                    <div class="icon"><i class="fa fa-file-text"></i></div>
                    <div class="content">
                        <div class="text">VACCINATED RECORDS</div>
                        <div class="number"><?= $totalCompleted ?></div>
                    </div>
                </div>
                <a href="vaccinationReport.php" class="card-footer text-white text-center d-block py-2" style="background: rgba(0,0,0,0.1); text-decoration:none;">
                    View History <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card info-box-2 bg-cyan">
                <div class="body">
                    <div class="icon"><i class="fa fa-hospital-o"></i></div>
                    <div class="content">
                        <div class="text">HOSPITAL LIST</div>
                        <div class="number"><?= $totalHospitals ?></div>
                    </div>
                </div>
                <a href="bookHospital.php" class="card-footer text-white text-center d-block py-2" style="background: rgba(0,0,0,0.1); text-decoration:none;">
                    Request Booking <i class="fa fa-arrow-circle-right ms-1"></i>
                </a>
            </div>
        </div>
    </div> <div class="row clearfix">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Upcoming</strong> Schedules Notification</h2>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Child Name</th>
                                    <th>Vaccine Name</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i=1;
                                if(mysqli_num_rows($upcomingList) > 0): 
                                    while($u = mysqli_fetch_assoc($upcomingList)): 
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= htmlspecialchars($u['child_name']) ?></strong></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($u['vaccine_name']) ?></span></td>
                                    <td><?= date('d M, Y', strtotime($u['booking_date'])) ?></td>
                                    <td><span class="badge badge-warning"><?= $u['status'] ?></span></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No upcoming vaccinations found.</td>
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