<?php
$pageTitle = "Upcoming Vaccinations";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Upcoming Vaccinations
   ========================= */
$result = mysqli_query($conn, "
    SELECT 
        c.child_name,
        b.vaccine_name,
        b.booking_date
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC
");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<h4 class="mb-3">Upcoming Vaccinations</h4>

<table class="table table-striped table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>Child</th>
            <th>Vaccine</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($r['child_name']) ?></td>
                    <td><?= htmlspecialchars($r['vaccine_name']) ?></td>
                    <td><?= htmlspecialchars($r['booking_date']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-muted">No upcoming vaccinations found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../base/footer.php'; ?>
