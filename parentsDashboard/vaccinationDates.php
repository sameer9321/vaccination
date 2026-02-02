<?php
session_start();

$pageTitle = "Vaccination Dates";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

/* parent_id from session (already fixed earlier) */
$parentId = (int)($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0) {
    die("Parent not linked. Please log out and log in again.");
}

/*
  Fetch upcoming vaccinations
*/
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
    WHERE c.parent_id = ?
      AND b.booking_date >= CURDATE()
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

<style>
.vacc_card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.table thead th{
    background:#f5f6fa;
}
.badge-status{
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
}
.badge-pending{ background:#fff3cd; color:#856404; }
.badge-done{ background:#d4edda; color:#155724; }
.badge-other{ background:#e2e3e5; color:#383d41; }
</style>

<div class="container-fluid">
    <div class="block-header">
        <ul class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a>
            </li>
            <li class="breadcrumb-item active">Vaccination Dates</li>
        </ul>
    </div>

    <div class="card vacc_card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="m-0">Upcoming Vaccinations</h4>
                <p class="text-muted m-0">
                    Vaccination schedules for your children
                </p>
            </div>
            <span class="badge bg-primary">
                Total: <?= count($vaccinations) ?>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th style="width:70px;">#</th>
                        <th>Child Name</th>
                        <th>Vaccine</th>
                        <th>Hospital</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (count($vaccinations) > 0): ?>
                    <?php $i = 1; foreach ($vaccinations as $v): ?>
                        <?php
                            $status = strtolower($v["status"] ?? "");
                            $badge = "badge-other";
                            if ($status === "pending") $badge = "badge-pending";
                            if (in_array($status, ["done","completed","vaccinated"])) $badge = "badge-done";
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($v["child_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars($v["vaccine_name"] ?? "") ?></td>
                            <td><?= htmlspecialchars($v["hospital_name"] ?? "—") ?></td>
                            <td><?= htmlspecialchars($v["booking_date"] ?? "") ?></td>
                            <td>
                                <span class="badge-status <?= $badge ?>">
                                    <?= htmlspecialchars($v["status"] ?? "Pending") ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-muted">
                            No upcoming vaccinations found.
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
