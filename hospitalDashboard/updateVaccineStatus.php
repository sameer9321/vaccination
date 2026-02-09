<?php
session_start();
$pageTitle = "Update Vaccine Status";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);
if ($hospitalId <= 0) {
    die("Hospital session expired. Please log in again.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "N/A";
    try {
        $dob = new DateTime($birthDate);
        $diff = (new DateTime())->diff($dob);
        return ($diff->y > 0) ? $diff->y . " yrs" : (($diff->m > 0) ? $diff->m . " mos" : $diff->d . " days");
    } catch (Exception $e) { return "N/A"; }
}

if (isset($_POST["status_update"])) {
    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $status = trim((string)($_POST["status"] ?? ""));
    $allowed = ["Pending", "Vaccinated", "Not Vaccinated", "Completed"];

    if ($bookingId > 0 && in_array($status, $allowed)) {
        $stmtUp = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?");
        mysqli_stmt_bind_param($stmtUp, "sii", $status, $bookingId, $hospitalId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        header("Location: updateVaccineStatus.php?updated=1");
        exit;
    }
}

$rows = [];
$stmt = mysqli_prepare($conn, "
    SELECT b.id AS booking_id, c.child_name, c.birth_date, b.vaccine_name, b.booking_date, b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.hospital_id = ?
    ORDER BY b.booking_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $hospitalId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<style>
    .cardBox { border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.05); border:none; background:#fff; padding:20px; }
    .table thead th { background:#f8f9fa; text-transform:uppercase; font-size:11px; letter-spacing:1px; }
    .pill { padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; }
    .pillPending { background:#fff3cd; color:#856404; }
    .pillDone { background:#d4edda; color:#155724; }
    .pillBad { background:#f8d7da; color:#842029; }
    .highlight-row { background-color: #fff9c4 !important; }
</style>

<div class="container-fluid">
    <div class="block-header">
        <h2>Vaccination Records <small>Manage and update patient statuses</small></h2>
    </div>

    <?php if (isset($_GET["updated"])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Updated!</strong> Patient record has been modified.
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <div class="cardBox">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search by child or vaccine name...">
            </div>
            <div class="col-md-6 text-right">
                <span class="text-muted">Showing <strong><?= count($rows) ?></strong> total appointments</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="statusTable">
                <thead>
                    <tr>
                        <th>Child Name</th>
                        <th>Age</th>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): foreach ($rows as $row): 
                        $st = strtolower($row["status"]);
                        $cls = "pill";
                        if ($st === "pending") $cls .= " pillPending";
                        elseif (in_array($st, ["vaccinated","done","completed"])) $cls .= " pillDone";
                        elseif ($st === "not vaccinated") $cls .= " pillBad";
                        
                        // Check if this row was targeted from dashboard
                        $isFocused = (isset($_GET['focus']) && $_GET['focus'] == $row['booking_id']) ? 'highlight-row' : '';
                    ?>
                    <tr class="<?= $isFocused ?>">
                        <td><strong><?= htmlspecialchars($row["child_name"]) ?></strong></td>
                        <td><?= calc_age_text($row["birth_date"]) ?></td>
                        <td><span class="badge badge-light border"><?= htmlspecialchars($row["vaccine_name"]) ?></span></td>
                        <td><?= date('d M Y', strtotime($row["booking_date"])) ?></td>
                        <td><span class="<?= $cls ?>"><?= strtoupper($row["status"]) ?></span></td>
                        <td>
                            <form method="post" class="d-flex gap-2 justify-content-center align-items-center m-0" onsubmit="return confirm('Update this status?');">
                                <input type="hidden" name="booking_id" value="<?= $row["booking_id"] ?>">
                                <select name="status" class="form-control form-control-sm mr-2" style="width:140px;">
                                    <?php
                                    foreach (["Pending","Vaccinated","Not Vaccinated","Completed"] as $opt) {
                                        $sel = ($opt === $row["status"]) ? "selected" : "";
                                        echo "<option value='$opt' $sel>$opt</option>";
                                    }
                                    ?>
                                </select>
                                <button type="submit" name="status_update" class="btn btn-sm btn-primary btn-round">
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('tableSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#statusTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let firstCol = rows[i].cells[0].textContent.toUpperCase();
        let thirdCol = rows[i].cells[2].textContent.toUpperCase();
        if (firstCol.indexOf(filter) > -1 || thirdCol.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }      
    }
});
</script>

<?php include "../base/footer.php"; ?>