<?php
$pageTitle = "Upcoming Vaccinations";
include '../base/header.php';
include '../includes/db.php';

$query = "
    SELECT 
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        DATEDIFF(b.booking_date, CURDATE()) as days_remaining
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.booking_date >= CURDATE() 
    AND (b.status IS NULL OR b.status NOT IN ('Completed', 'Vaccinated', 'Done'))
    ORDER BY b.booking_date ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<style>
    .date-card { border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); padding: 25px; background: #fff; }
    .status-urgent { color: #dc3545; font-weight: 700; }
    .status-upcoming { color: #0d6efd; font-weight: 600; }
</style>

<div class="container py-4">
    <div class="card date-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0"><i class="fa fa-calendar text-primary me-2"></i>Upcoming Schedules</h4>
            <span class="badge bg-info p-2">Total Upcoming: <?= mysqli_num_rows($result); ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover border align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Child Name</th>
                        <th>Vaccine Type</th>
                        <th>Scheduled Date</th>
                        <th>Time Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($result)): 
                            $days = $r['days_remaining'];
                            $timeLabel = ($days == 0) ? "Today" : (($days == 1) ? "Tomorrow" : $days . " days left");
                            $labelClass = ($days <= 2) ? "status-urgent" : "status-upcoming";
                        ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($r['child_name']) ?></td>
                                <td><span class="badge btn-outline-secondary text-dark border"><?= htmlspecialchars($r['vaccine_name']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($r['booking_date'])) ?></td>
                                <td class="<?= $labelClass ?>">
                                    <i class="fa fa-clock-o me-1"></i> <?= $timeLabel ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-5 text-muted">
                                <i class="fa fa-info-circle fa-2x mb-2"></i><br>
                                No upcoming vaccinations scheduled.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../base/footer.php'; ?>