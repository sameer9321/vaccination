<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

if (isset($_POST["update_status"])) {
    $bid = (int)$_POST["booking_id"];
    $status = $_POST["status"];
    $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $status, $bid, $hospitalId);
    mysqli_stmt_execute($stmt);
    header("Location: appointments.php?updated=1");
    exit;
}

$view = $_GET['view'] ?? 'upcoming';
$dateFilter = ($view === 'all') ? "" : "AND b.booking_date >= CURDATE()";

$query = "SELECT b.*, c.child_name FROM bookings b 
          JOIN children c ON b.child_id = c.child_id 
          WHERE b.hospital_id = $hospitalId $dateFilter 
          ORDER BY b.booking_date ASC";
$result = mysqli_query($conn, $query);

include "../base/header.php"; 
?>

<div class="block-header">
    <div class="row">
        <div class="col-md-6">
            <h2>Appointments</h2>
        </div>
        <div class="col-md-6 text-right">
            <a href="appointments.php?view=upcoming" class="btn btn-sm <?= $view!='all' ? 'btn-primary' : 'btn-outline-primary' ?>">Upcoming</a>
            <a href="appointments.php?view=all" class="btn btn-sm <?= $view=='all' ? 'btn-primary' : 'btn-outline-primary' ?>">All History</a>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="body">
                <input type="text" id="aptSearch" class="form-control mb-3" placeholder="Search child name...">
                <div class="table-responsive">
                    <table class="table table-hover" id="aptTable">
                        <thead>
                            <tr>
                                <th>Child</th>
                                <th>Vaccine</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['child_name']) ?></td>
                                <td><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                <td><?= $row['booking_date'] ?></td>
                                <td><span class="badge badge-info"><?= $row['status'] ?></span></td>
                                <td>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                        <select name="status" class="form-control form-control-sm mr-2">
                                            <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                            <option value="Vaccinated" <?= $row['status']=='Vaccinated'?'selected':'' ?>>Vaccinated</option>
                                            <option value="Completed" <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('aptSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#aptTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        rows[i].style.display = rows[i].cells[0].textContent.toUpperCase().includes(filter) ? "" : "none";
    }
});
</script>

<?php include "../base/footer.php"; ?>