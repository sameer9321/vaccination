<?php
$pageTitle = "Vaccination Reports";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Fetch Vaccination Reports
   ========================= */
$result = mysqli_query($conn, "
    SELECT 
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    ORDER BY b.booking_date DESC
");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<style>
.report-card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:25px;
}
.table th{
    background:#f5f6fa;
}
.status-pill{
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}
.status-pending{ background:#fff3cd; color:#856404; }
.status-done{ background:#d4edda; color:#155724; }
.status-other{ background:#e2e3e5; color:#383d41; }
</style>

<div class="card report-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">Vaccination Reports</h4>
        <span class="badge bg-primary">
            Total: <?= mysqli_num_rows($result); ?>
        </span>
    </div>

    <table class="table table-striped table-bordered table-hover text-center align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Child</th>
                <th>Vaccine</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php
        $i = 1;
        if (mysqli_num_rows($result) > 0):
            while ($r = mysqli_fetch_assoc($result)):

                $statusRaw = $r['status'] ?? '';
                $status = trim((string)$statusRaw);

                $badgeClass = 'status-other';
                if (strtolower($status) === 'pending') $badgeClass = 'status-pending';
                if (in_array(strtolower($status), ['done','completed','vaccinated'], true)) $badgeClass = 'status-done';
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['child_name']) ?></td>
                <td><?= htmlspecialchars($r['vaccine_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['booking_date'] ?? '') ?></td>
                <td>
                    <span class="status-pill <?= $badgeClass ?>">
                        <?= htmlspecialchars($status ?: 'N/A') ?>
                    </span>
                </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="5" class="text-muted text-center">
                    No vaccination records found.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../base/footer.php'; ?>
