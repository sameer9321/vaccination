<?php
session_start();
$pageTitle = "Hospital Dashboard";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "hospital") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

if ($hospitalId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = mysqli_fetch_assoc($resU);
        $userEmail = $rowU["email"] ?? "";
        mysqli_stmt_close($stmtU);

        if ($userEmail !== "") {
            $stmtH = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtH, "s", $userEmail);
            mysqli_stmt_execute($stmtH);
            $resH = mysqli_stmt_get_result($stmtH);
            if ($rowH = mysqli_fetch_assoc($resH)) {
                $hospitalId = (int)$rowH["id"];
                $_SESSION["hospital_id"] = $hospitalId;
                $_SESSION["hospital_name"] = $rowH["hospital_name"];
            }
            mysqli_stmt_close($stmtH);
        }
    }
}

if ($hospitalId <= 0) {
    die("Hospital account not linked. Please log out and log in again.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "N/A";
    $dob = new DateTime($birthDate);
    $diff = (new DateTime())->diff($dob);
    return ($diff->y > 0) ? $diff->y . " yrs" : (($diff->m > 0) ? $diff->m . " mos" : $diff->d . " days");
}

/* Statistics */
$totalAppointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE hospital_id = $hospitalId"))['total'];
$totalPending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE hospital_id = $hospitalId AND LOWER(status) = 'pending'"))['total'];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospital_requests WHERE requested_hospital = (SELECT hospital_name FROM hospitals WHERE id = $hospitalId) AND status = 'Pending'"))['total'];

$appointments = [];
$resA = mysqli_query($conn, "SELECT b.id as booking_id, c.child_name, c.birth_date, b.vaccine_name, b.booking_date, b.status 
    FROM bookings b JOIN children c ON c.child_id = b.child_id 
    WHERE b.hospital_id = $hospitalId AND b.booking_date >= CURDATE() 
    ORDER BY b.booking_date ASC LIMIT 10");
while ($rowA = mysqli_fetch_assoc($resA)) $appointments[] = $rowA;

include "../base/header.php";
?>

<style>
    .topCard { border-radius:16px; padding:20px; background:#fff; box-shadow:0 8px 22px rgba(0,0,0,0.05); margin-bottom:25px; border-left: 5px solid #007bff; }
    .statPill { border-radius:14px; padding:15px; background:#f8f9fa; min-width:130px; text-align:center; border: 1px solid #eee; }
    .statNum { font-size:24px; font-weight:800; color: #222; margin:0; }
    .statLbl { font-size:11px; text-transform: uppercase; letter-spacing: 1px; color:#888; margin:0; }
    .featureCard { border-radius:16px; box-shadow:0 6px 15px rgba(0,0,0,0.05); transition: 0.3s; border:none; }
    .featureCard:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .iconCircle { width:55px; height:55px; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; }
    .bg-light-blue { background: #e7f1ff; color: #007bff; }
    .bg-light-green { background: #e8f5e9; color: #2e7d32; }
    .bg-light-orange { background: #fff3e0; color: #ef6c00; }
    .pill { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:bold; text-transform: uppercase; }
    .pillPending { background:#fff3cd; color:#856404; }
    .pillDone { background:#d4edda; color:#155724; }
</style>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="hospitalDashboard.php"><i class="fa fa-hospital-o"></i></a></li>
                    <li class="breadcrumb-item active">Overview</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="topCard">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="m-0">Welcome, <?= htmlspecialchars($_SESSION["hospital_name"] ?? "Provider") ?></h3>
                <p class="text-muted m-0">Healthcare facility portal for vaccine management.</p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex justify-content-end gap-3 flex-wrap">
                    <div class="statPill">
                        <p class="statNum"><?= $totalAppointments ?></p>
                        <p class="statLbl">Total Slots</p>
                    </div>
                    <div class="statPill">
                        <p class="statNum text-warning"><?= $totalPending ?></p>
                        <p class="statLbl">Pending</p>
                    </div>
                    <div class="statPill">
                        <p class="statNum text-primary"><?= $totalRequests ?></p>
                        <p class="statLbl">New Requests</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card featureCard text-center p-4">
                <div class="iconCircle bg-light-blue"><i class="fa fa-check-square-o fa-lg"></i></div>
                <h6>Update Status</h6>
                <p class="small text-muted">Process completed vaccinations.</p>
                <a href="updateVaccineStatus.php" class="btn btn-outline-primary btn-sm btn-round">Go to Updates</a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card featureCard text-center p-4">
                <div class="iconCircle bg-light-green"><i class="fa fa-users fa-lg"></i></div>
                <h6>Parent Requests</h6>
                <p class="small text-muted">Manage new hospital associations.</p>
                <a href="manageRequests.php" class="btn btn-outline-success btn-sm btn-round">
                    View (<?= $totalRequests ?>)
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card featureCard text-center p-4">
                <div class="iconCircle bg-light-orange"><i class="fa fa-calendar-check-o fa-lg"></i></div>
                <h6>Schedules</h6>
                <p class="small text-muted">Full appointment calendar view.</p>
                <a href="appointments.php" class="btn btn-outline-warning btn-sm btn-round">View All</a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card featureCard text-center p-4">
                <div class="iconCircle bg-light-blue"><i class="fa fa-file-pdf-o fa-lg"></i></div>
                <h6>Medical Reports</h6>
                <p class="small text-muted">Generate & upload patient records.</p>
                <a href="reportes.php" class="btn btn-outline-info btn-sm btn-round">View Reports</a>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-12">
            <div class="card notifyCard">
                <div class="notifyHead bg-white">
                    <h6 class="m-0"><strong>Upcoming</strong> Vaccination Slots</h6>
                    <a href="appointments.php" class="small">View All</a>
                </div>
                <div class="body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Child Name</th>
                                    <th>Current Age</th>
                                    <th>Vaccine</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)): foreach ($appointments as $row): 
                                    $st = strtolower($row['status']);
                                    $pillCls = ($st === 'pending') ? 'pillPending' : 'pillDone';
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row["child_name"]) ?></strong></td>
                                    <td><?= calc_age_text($row["birth_date"]) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($row["vaccine_name"]) ?></span></td>
                                    <td><?= date('d M, Y', strtotime($row["booking_date"])) ?></td>
                                    <td><span class="pill <?= $pillCls ?>"><?= strtoupper($row["status"]) ?></span></td>
                                    <td>
                                        <a href="updateVaccineStatus.php?id=<?= $row['booking_id'] ?>" class="btn btn-sm btn-primary">Update</a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center py-4">No appointments scheduled today.</td></tr>
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