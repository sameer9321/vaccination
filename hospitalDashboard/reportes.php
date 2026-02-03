<?php
session_start();
$pageTitle = "Vaccination Reports";
include '../base/header.php';
include '../includes/db.php';

/* ============================================================
   Fetch Vaccination Reports
   Logic: Get records for this specific hospital if logged in as hospital,
   otherwise get all (depending on your admin needs).
   ============================================================ */
$hospitalId = $_SESSION['hospital_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

// If a hospital is logged in, only show THEIR reports
$whereClause = ($role === 'hospital') ? "WHERE b.hospital_id = $hospitalId" : "";

$query = "SELECT c.child_name, b.vaccine_name, b.booking_date, b.status
          FROM bookings b
          JOIN children c ON c.child_id = b.child_id
          $whereClause
          ORDER BY b.booking_date DESC";

$result = mysqli_query($conn, $query);
?>

<style>
    .report-card { border-radius:15px; box-shadow:0 8px 25px rgba(0,0,0,0.05); padding:25px; background:#fff; border:none; }
    .status-pill { padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; display:inline-block; text-transform:uppercase; }
    .status-pending { background:#fff3cd; color:#856404; }
    .status-done { background:#d4edda; color:#155724; }
    .status-other { background:#e9ecef; color:#495057; }
    
    /* Print Styling */
    @media print {
        .no-print, .sidebar, .navbar { display: none !important; }
        .content { margin: 0 !important; padding: 0 !important; }
        .report-card { box-shadow: none; border: 1px solid #eee; }
    }
</style>

<section class="content">
    <div class="container-fluid">
        <div class="block-header no-print">
            <h2>VACCINATION REPORTS</h2>
        </div>

        <div class="card report-card">
            <div class="header no-print">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h4 class="m-0">Detailed Logs</h4>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="d-flex justify-content-end align-items-center" style="gap:10px;">
                            <input type="text" id="reportSearch" class="form-control" style="max-width:200px;" placeholder="Search by child...">
                            <button onclick="window.print()" class="btn btn-primary btn-sm">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                            <span class="badge bg-blue">Records: <?= mysqli_num_rows($result); ?></span>
                        </div>
                    </div>
                </div>
                <hr>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="reportTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Child Name</th>
                            <th>Vaccine Type</th>
                            <th>Administered Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        if (mysqli_num_rows($result) > 0):
                            while ($r = mysqli_fetch_assoc($result)):
                                $status = trim((string)$r['status']);
                                $badgeClass = 'status-other';
                                if (strtolower($status) === 'pending') $badgeClass = 'status-pending';
                                if (in_array(strtolower($status), ['done','completed','vaccinated'], true)) $badgeClass = 'status-done';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($r['child_name']) ?></strong></td>
                                <td><span class="text-primary"><?= htmlspecialchars($r['vaccine_name'] ?? 'N/A') ?></span></td>
                                <td><?= date('M d, Y', strtotime($r['booking_date'])) ?></td>
                                <td>
                                    <span class="status-pill <?= $badgeClass ?>">
                                        <?= htmlspecialchars($status ?: 'Unknown') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No vaccination records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
// Real-time search filter
document.getElementById('reportSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#reportTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let childName = rows[i].cells[1].textContent.toUpperCase();
        rows[i].style.display = childName.includes(filter) ? "" : "none";
    }
});
</script>

<?php include '../base/footer.php'; ?>