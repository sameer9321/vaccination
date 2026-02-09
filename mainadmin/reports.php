<?php
$pageTitle = "Vaccination Reports";
include '../base/header.php';
include '../includes/db.php';

$whereCondition = '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

// Sanitize and build the date filter
if (!empty($startDate) && !empty($endDate)) {
    $s = mysqli_real_escape_string($conn, $startDate);
    $e = mysqli_real_escape_string($conn, $endDate);
    $whereCondition = " AND b.booking_date BETWEEN '$s' AND '$e'";
}

if (isset($_POST['export_csv'])) {
    ob_end_clean(); 
    
    $csvFileName = 'vaccination_report_' . date('Y_m_d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $csvFileName . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Child Name', 'Vaccine Name', 'Booking Date', 'Status']);
    $exportQuery = "
        SELECT c.child_name, b.vaccine_name, b.booking_date, b.status
        FROM bookings b
        JOIN children c ON c.child_id = b.child_id
        WHERE 1=1 $whereCondition
        ORDER BY b.booking_date DESC";
    
    $exportResult = mysqli_query($conn, $exportQuery);
    while ($row = mysqli_fetch_assoc($exportResult)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

$result = mysqli_query($conn, "
    SELECT c.child_name, b.vaccine_name, b.booking_date, b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE 1=1 $whereCondition
    ORDER BY b.booking_date DESC
");
?>

<style>
.report-card { border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,0.08); padding:25px; background:#fff; }
.status-pill { padding:6px 12px; border-radius:999px; font-size:12px; font-weight:600; display:inline-block; }
.status-pending { background:#fff3cd; color:#856404; }
.status-done { background:#d4edda; color:#155724; }
.status-other { background:#e2e3e5; color:#383d41; }
</style>

<div class="container py-4">
    <div class="card report-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0"><i class="fa fa-file-text text-primary me-2"></i>Vaccination Reports</h4>
            <span class="badge bg-primary p-2">Total Records: <?= mysqli_num_rows($result); ?></span>
        </div>

        <form method="POST" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="small fw-bold">From Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="small fw-bold">To Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 me-2">Filter</button>
                <button type="submit" name="export_csv" class="btn btn-success w-100">Export CSV</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover border">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Child Name</th>
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
                        $status = strtolower(trim($r['status'] ?? 'pending'));
                        $badgeClass = ($status === 'pending') ? 'status-pending' : 
                                      (in_array($status, ['done','completed','vaccinated']) ? 'status-done' : 'status-other');
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['child_name']) ?></td>
                        <td><?= htmlspecialchars($r['vaccine_name'] ?: 'N/A') ?></td>
                        <td><?= date('M d, Y', strtotime($r['booking_date'])) ?></td>
                        <td>
                            <span class="status-pill <?= $badgeClass ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No records found for the selected dates.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../base/footer.php'; ?>