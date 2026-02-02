<?php
session_start();

$pageTitle = "Update Vaccine Status";
include "../includes/db.php";

/* Hospital auth */
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);
if ($hospitalId <= 0) {
    die("Hospital id not found in session. Please log out and log in again.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "";
    try {
        $dob = new DateTime($birthDate);
        $now = new DateTime();
        $diff = $now->diff($dob);

        $y = (int)$diff->y;
        $m = (int)$diff->m;
        $d = (int)$diff->d;

        if ($y > 0) return $y . " yrs";
        if ($m > 0) return $m . " months";
        return $d . " days";
    } catch (Exception $e) {
        return "";
    }
}

/* Update status */
if (isset($_POST["status_update"])) {
    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $status = trim((string)($_POST["status"] ?? ""));

    $allowed = ["Pending", "Vaccinated", "Not Vaccinated", "Completed"];
    if ($bookingId <= 0 || !in_array($status, $allowed, true)) {
        header("Location: updateVaccineStatus.php?error=1");
        exit;
    }

    $stmtUp = mysqli_prepare(
        $conn,
        "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?"
    );

    if (!$stmtUp) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtUp, "sii", $status, $bookingId, $hospitalId);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    header("Location: updateVaccineStatus.php?updated=1");
    exit;
}

/* Fetch bookings for this hospital */
$rows = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        b.id AS booking_id,
        c.child_name,
        c.birth_date,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.hospital_id = ?
    ORDER BY b.booking_date DESC, b.id DESC
");

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $hospitalId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($res && ($r = mysqli_fetch_assoc($res))) {
    $rows[] = $r;
}
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<style>
.cardBox{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
    background:#fff;
    padding:18px;
    margin-bottom:16px;
}
.table thead th{
    background:#f5f6fa;
}
.pill{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    background:#e2e3e5;
    color:#383d41;
}
.pillPending{ background:#fff3cd; color:#856404; }
.pillDone{ background:#d4edda; color:#155724; }
.pillBad{ background:#f8d7da; color:#842029; }
.msgOk{
    padding:10px 12px;
    border-radius:12px;
    background:#d4edda;
    color:#155724;
    margin-bottom:12px;
}
.msgErr{
    padding:10px 12px;
    border-radius:12px;
    background:#f8d7da;
    color:#842029;
    margin-bottom:12px;
}
</style>

<div class="container-fluid">

    <?php if (isset($_GET["updated"])): ?>
        <div class="msgOk">Status updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>
        <div class="msgErr">Invalid data. Please try again.</div>
    <?php endif; ?>

    <div class="cardBox">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="m-0">Update Vaccine Status</h3>
                <div class="text-muted" style="font-size:13px;">Update status for appointments booked at your hospital</div>
            </div>
            <span class="badge bg-primary">Total: <?= (int)count($rows) ?></span>
        </div>

        <div style="overflow:auto;">
            <table class="table table-bordered table-striped text-center align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Child</th>
                        <th>Age</th>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="width:260px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) > 0): ?>
                        <?php $i=1; foreach ($rows as $row): ?>
                            <?php
                                $st = strtolower((string)($row["status"] ?? "pending"));
                                $cls = "pill";
                                if ($st === "pending") $cls = "pill pillPending";
                                if (in_array($st, ["vaccinated","done","completed"], true)) $cls = "pill pillDone";
                                if ($st === "not vaccinated") $cls = "pill pillBad";
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row["child_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars(calc_age_text($row["birth_date"] ?? "")) ?></td>
                                <td><?= htmlspecialchars($row["vaccine_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($row["booking_date"] ?? "") ?></td>
                                <td><span class="<?= $cls ?>"><?= htmlspecialchars($row["status"] ?? "Pending") ?></span></td>
                                <td>
                                    <form method="post" class="d-flex gap-2 justify-content-center align-items-center flex-wrap m-0">
                                        <input type="hidden" name="booking_id" value="<?= (int)($row["booking_id"] ?? 0) ?>">
                                        <select name="status" class="form-control form-control-sm" style="max-width:170px;">
                                            <?php
                                                $current = (string)($row["status"] ?? "Pending");
                                                $opts = ["Pending","Vaccinated","Not Vaccinated","Completed"];
                                                foreach ($opts as $opt) {
                                                    $sel = ($opt === $current) ? "selected" : "";
                                                    echo "<option value=\"" . htmlspecialchars($opt) . "\" $sel>" . htmlspecialchars($opt) . "</option>";
                                                }
                                            ?>
                                        </select>
                                        <button type="submit" name="status_update" class="btn btn-sm btn-success">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-muted text-center">No appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include "../base/footer.php"; ?>
