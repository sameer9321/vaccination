<?php
session_start();

$pageTitle = "Appointments";
include "../includes/db.php";

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
if (isset($_POST["update_status"])) {
    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $newStatus = trim((string)($_POST["status"] ?? ""));

    $allowed = ["Pending", "Vaccinated", "Not Vaccinated", "Completed", "Done"];
    if ($bookingId > 0 && in_array($newStatus, $allowed, true)) {

        $stmtUp = mysqli_prepare(
            $conn,
            "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?"
        );

        if (!$stmtUp) {
            die("Prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmtUp, "sii", $newStatus, $bookingId, $hospitalId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);

        header("Location: appointments.php?updated=1");
        exit;
    } else {
        header("Location: appointments.php?error=1");
        exit;
    }
}

/* Filter */
$view = strtolower((string)($_GET["view"] ?? "upcoming"));
$whereDate = "";
if ($view === "all") {
    $whereDate = "";
} else {
    $whereDate = "AND b.booking_date >= CURDATE()";
}

/* Fetch appointments */
$rows = [];

$sql = "
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
    $whereDate
    ORDER BY b.booking_date ASC, b.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
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
.headRow{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
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
.btnLite{
    display:inline-block;
    padding:8px 12px;
    border-radius:10px;
    border:1px solid #0d6efd;
    color:#0d6efd;
    text-decoration:none;
    font-size:13px;
}
.btnLiteActive{
    background:#0d6efd;
    color:#fff;
}
.tableWrap{
    overflow:auto;
}
table{
    width:100%;
    border-collapse:collapse;
}
th, td{
    border:1px solid #eef0f5;
    padding:10px;
    text-align:center;
    vertical-align:middle;
}
th{
    background:#f5f6fa;
}
.smallNote{
    font-size:12px;
    color:#6c757d;
}
select, button{
    padding:7px 10px;
    border-radius:10px;
    border:1px solid #dfe3ea;
}
button{
    background:#198754;
    color:#fff;
    border:none;
    cursor:pointer;
}
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

    <div class="cardBox">
        <div class="headRow">
            <div>
                <h3 style="margin:0;">Appointments</h3>
                <div class="smallNote">Update vaccination status for your hospital bookings</div>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <a class="btnLite <?php echo ($view !== 'all') ? 'btnLiteActive' : ''; ?>" href="appointments.php?view=upcoming">Upcoming</a>
                <a class="btnLite <?php echo ($view === 'all') ? 'btnLiteActive' : ''; ?>" href="appointments.php?view=all">All</a>
                <span class="pill">Total: <?php echo (int)count($rows); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET["updated"])): ?>
        <div class="msgOk">Status updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET["error"])): ?>
        <div class="msgErr">Invalid data. Please try again.</div>
    <?php endif; ?>

    <div class="cardBox">
        <div class="tableWrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Child</th>
                        <th>Age</th>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="width:260px;">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) > 0): ?>
                        <?php $i = 1; foreach ($rows as $row): ?>
                            <?php
                                $st = strtolower((string)($row["status"] ?? ""));
                                $cls = "pill";
                                if ($st === "pending") $cls = "pill pillPending";
                                if (in_array($st, ["vaccinated","done","completed"], true)) $cls = "pill pillDone";
                            ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars((string)($row["child_name"] ?? "")); ?></td>
                                <td><?php echo htmlspecialchars(calc_age_text((string)($row["birth_date"] ?? ""))); ?></td>
                                <td><?php echo htmlspecialchars((string)($row["vaccine_name"] ?? "")); ?></td>
                                <td><?php echo htmlspecialchars((string)($row["booking_date"] ?? "")); ?></td>
                                <td><span class="<?php echo $cls; ?>"><?php echo htmlspecialchars((string)($row["status"] ?? "Pending")); ?></span></td>
                                <td>
                                    <form method="post" style="display:flex; gap:8px; justify-content:center; align-items:center; flex-wrap:wrap; margin:0;">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)($row["booking_id"] ?? 0); ?>">
                                        <select name="status">
                                            <?php
                                                $current = (string)($row["status"] ?? "Pending");
                                                $opts = ["Pending", "Vaccinated", "Not Vaccinated", "Completed"];
                                                foreach ($opts as $opt) {
                                                    $sel = ($opt === $current) ? "selected" : "";
                                                    echo "<option value=\"" . htmlspecialchars($opt) . "\" $sel>" . htmlspecialchars($opt) . "</option>";
                                                }
                                            ?>
                                        </select>
                                        <button type="submit" name="update_status">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="color:#6c757d;">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="smallNote" style="margin-top:10px;">
            Tip: If you mark Vaccinated or Completed, parent will see it in Vaccination Report.
        </div>
    </div>

</div>

<?php include "../base/footer.php"; ?>
