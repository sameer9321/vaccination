<?php
$pageTitle = "Bookings";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Fetch Bookings
   ========================= */
$result = mysqli_query($conn, "
    SELECT 
        c.child_name,
        h.hospital_name AS hospital,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    JOIN hospitals h ON h.id = b.hospital_id
    ORDER BY b.booking_date DESC
");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<style>
.booking-card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
}
.table th{
    background:#f5f6fa;
}
.status-pill{
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}
.status-pending{ background:#fff3cd; color:#856404; }
.status-done{ background:#d4edda; color:#155724; }
.status-other{ background:#e2e3e5; color:#383d41; }
</style>

<div class="card booking-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">Bookings</h4>
        <span class="badge bg-primary">Total: <?= $total ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Child</th>
                    <th>Hospital</th>
                    <th>Vaccine</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0): ?>
                    <?php $i=1; while ($b = mysqli_fetch_assoc($result)): 
                        $status = strtolower($b['status'] ?? '');
                        $badge = 'status-other';
                        if ($status === 'pending') $badge = 'status-pending';
                        if (in_array($status, ['done','completed','vaccinated'], true)) $badge = 'status-done';
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($b['child_name']) ?></td>
                            <td><?= htmlspecialchars($b['hospital']) ?></td>
                            <td><?= htmlspecialchars($b['vaccine_name']) ?></td>
                            <td><?= htmlspecialchars($b['booking_date']) ?></td>
                            <td>
                                <span class="status-pill <?= $badge ?>">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-muted">
                            No bookings found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../base/footer.php'; ?>
