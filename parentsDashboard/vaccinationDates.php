<?php
session_start();
$pageTitle = "Vaccination Dates";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$parentId = (int)($_SESSION["parent_id"] ?? 0);
if ($parentId <= 0) {
    die("Parent profile not found. Please log in again.");
}

$vaccinations = [];
$stmt = mysqli_prepare($conn, "
    SELECT 
        c.child_name, 
        b.vaccine_name, 
        b.booking_date, 
        b.status, 
        h.hospital_name 
    FROM bookings b 
    JOIN children c ON c.child_id = b.child_id 
    LEFT JOIN hospitals h ON h.id = b.hospital_id 
    WHERE c.parent_id = ? AND b.booking_date >= CURDATE() 
    ORDER BY b.booking_date ASC
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $parentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $vaccinations[] = $row;
    }
    mysqli_stmt_close($stmt);
}

include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Vaccination Schedule</h2>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Upcoming Dates</li>
                </ul>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 text-right">
                <a href="bookHospital.php" class="btn btn-sm btn-primary">Book New Appointment</a>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Upcoming</strong> Vaccinations <small>Notification of future dates for your children</small></h2>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0 text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Child Name</th>
                                    <th>Vaccine Name</th>
                                    <th>Hospital</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($vaccinations) > 0): ?>
                                    <?php $i = 1; foreach ($vaccinations as $v): ?>
                                        <?php
                                            $status = strtolower($v["status"] ?? "pending");
                                            $statusClass = "badge-warning"; // Default for Pending
                                            if (in_array($status, ["done", "completed", "vaccinated"])) {
                                                $statusClass = "badge-success";
                                            } elseif ($status == "cancelled" || $status == "rejected") {
                                                $statusClass = "badge-danger";
                                            }
                                        ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><strong><?= htmlspecialchars($v["child_name"]) ?></strong></td>
                                            <td><span class="badge bg-blue"><?= htmlspecialchars($v["vaccine_name"]) ?></span></td>
                                            <td><i class="fa fa-hospital-o me-1"></i> <?= htmlspecialchars($v["hospital_name"] ?? "Not Assigned") ?></td>
                                            <td><span class="text-primary fw-bold"><?= date('d M, Y', strtotime($v["booking_date"])) ?></span></td>
                                            <td>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= strtoupper($v["status"] ?? "PENDING") ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-4">
                                            <div class="text-muted">
                                                <i class="fa fa-calendar-times-o fa-3x mb-3"></i><br>
                                                No upcoming vaccinations found.
                                            </div>
                                        </td>
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