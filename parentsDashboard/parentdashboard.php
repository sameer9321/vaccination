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

// Auto-create/link parent profile if missing
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

// Statistics Queries
$totalChildren  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM children WHERE parent_id = $parentId"))['total'];
$totalUpcoming  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE()"))['total'];
$totalPending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospital_requests WHERE parent_id = $parentId AND status = 'Pending'"))['total'];
$totalCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.status IN ('Vaccinated', 'Completed')"))['total'];
$totalHospitals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospitals"))['total'];

$upcomingList = mysqli_query($conn, "SELECT c.child_name, b.vaccine_name, b.booking_date, b.status FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC LIMIT 4");

include "../base/header.php";
?>

<style>
    .section-title { font-weight: 700; margin: 30px 0 15px; color: #333; }
    .menu-card { 
        border-radius: 14px; 
        padding: 22px; 
        text-align: center; 
        box-shadow: 0 6px 18px rgba(0,0,0,0.08); 
        transition: .25s; 
        background: #fff;
        height: 100%;
    }
    .menu-card:hover { transform: translateY(-6px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
    .stat { font-size: 28px; font-weight: 700; display: block; }
    .stat-label { font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    .icon-box { color: #007bff; margin-bottom: 15px; }
    .card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
</style>

<div class="container-fluid py-4">
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ["Total Children", $totalChildren, "primary", "fa-child"],
            ["Upcoming", $totalUpcoming, "success", "fa-calendar-check-o"],
            ["Pending Requests", $totalPending, "warning", "fa-clock-o"],
            ["Vaccinated", $totalCompleted, "info", "fa-file-text"],
            ["Hospitals", $totalHospitals, "secondary", "fa-hospital-o"]
        ];

        foreach($stats as $s) {
        ?>
        <div class="col">
            <div class="menu-card bg-<?php echo $s[2]; ?> text-white">
                <i class="fa <?php echo $s[3]; ?> mb-2"></i>
                <span class="stat"><?php echo $s[1]; ?></span>
                <span class="stat-label"><?php echo $s[0]; ?></span>
            </div>
        </div>
        <?php } ?>
    </div>

    <h5 class="section-title"><i class="fa fa-th-large me-2"></i>Parent Services</h5>
    
    <div class="row g-4">
        <?php
        $cards = [
            ["Child Details", "childDetails.php", "fa-users", "Manage your registered children"],
            ["Vaccination Dates", "vaccinationDates.php", "fa-calendar", "View upcoming vaccine schedules"],
            ["Hospital Requests", "requestHospitals.php", "fa-send", "Track requests sent to hospitals"],
            ["Vaccination History", "vaccinationReport.php", "fa-history", "Download vaccination reports"],
            ["Book Hospital", "bookHospital.php", "fa-plus-square", "Find and book nearby hospitals"],
            ["Profile Settings", "profile.php", "fa-user-circle", "Update your contact information"]
        ];

        foreach($cards as $c) {
        ?>
        <div class="col-md-3">
            <div class="menu-card d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-box"><i class="fa <?php echo $c[2]; ?> fa-3x"></i></div>
                    <h6 class="fw-bold"><?php echo $c[0]; ?></h6>
                    <p class="small text-muted"><?php echo $c[3]; ?></p>
                </div>
                <a href="<?php echo $c[1]; ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">Access</a>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="row mt-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa fa-bell-o me-2"></i>Upcoming Vaccination Schedules</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                    <td><span class="fw-bold"><?= htmlspecialchars($u['child_name']) ?></span></td>
                                    <td><span class="badge bg-soft-primary text-primary border border-primary px-3"><?= htmlspecialchars($u['vaccine_name']) ?></span></td>
                                    <td><i class="fa fa-calendar-o me-1 text-muted"></i> <?= date('d M, Y', strtotime($u['booking_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-warning text-dark px-3"><?= $u['status'] ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No upcoming vaccinations found.</td>
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