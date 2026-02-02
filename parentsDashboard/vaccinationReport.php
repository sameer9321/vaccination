<?php
session_start();

$pageTitle = "Vaccination Report";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parent id if missing */
if ($parentId <= 0 && $userId > 0) {

    $userEmail = "";

    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = $resU ? mysqli_fetch_assoc($resU) : null;
        mysqli_stmt_close($stmtU);

        if ($rowU && isset($rowU["email"])) {
            $userEmail = (string)$rowU["email"];
        }
    }

    if ($userEmail !== "") {

        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        if ($stmtP) {
            mysqli_stmt_bind_param($stmtP, "s", $userEmail);
            mysqli_stmt_execute($stmtP);
            $resP = mysqli_stmt_get_result($stmtP);
            $rowP = $resP ? mysqli_fetch_assoc($resP) : null;
            mysqli_stmt_close($stmtP);

            if ($rowP && isset($rowP["parent_id"])) {
                $parentId = (int)$rowP["parent_id"];
            }
        }

        if ($parentId <= 0) {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, ?)");
            if ($stmtIns) {
                $blankPass = "";
                mysqli_stmt_bind_param($stmtIns, "sss", $username, $userEmail, $blankPass);
                mysqli_stmt_execute($stmtIns);
                $parentId = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmtIns);
            }
        }
    }

    if ($parentId > 0) {
        $_SESSION["parent_id"] = $parentId;
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please log out and log in again.");
}

/*
  Fetch previous vaccinations for this parent
  Logic:
  show completed records OR past date records
*/
$reports = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        b.status,
        h.hospital_name
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    LEFT JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ?
      AND (
        LOWER(IFNULL(b.status,'')) IN ('done','completed','vaccinated')
        OR b.booking_date < CURDATE()
      )
    ORDER BY b.booking_date DESC
");

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<style>
.cardBox{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.headCell{
    background:#f5f6fa;
}
.pill{
    display:inline-block;
    padding:6px 10px;
    border-radius:18px;
    font-size:12px;
}
.pillOk{ background:#d4edda; color:#155724; }
.pillWarn{ background:#fff3cd; color:#856404; }
.pillOther{ background:#e2e3e5; color:#383d41; }
.btnLike{
    display:inline-block;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    border:1px solid #0d6efd;
    color:#0d6efd;
}
.note{
    color:#666;
    margin:0;
}
</style>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
            <h3 style="margin:0;">Vaccination Report</h3>
            <p class="note">Previous vaccinations for your children</p>
        </div>
        <a class="btnLike" href="parentdashboard.php">Back</a>
    </div>

    <div class="cardBox">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="font-size:14px; color:#333;">History</div>
            <div style="background:#0d6efd; color:#fff; padding:6px 10px; border-radius:16px;">
                Total: <?= count($reports) ?>
            </div>
        </div>

        <div style="overflow:auto;">
            <table class="table table-bordered" style="width:100%; text-align:center; margin:0; vertical-align:middle;">
                <thead>
                    <tr>
                        <th class="headCell" style="width:70px;">No</th>
                        <th class="headCell">Child</th>
                        <th class="headCell">Vaccine</th>
                        <th class="headCell">Hospital</th>
                        <th class="headCell">Date</th>
                        <th class="headCell">Status</th>
                        <th class="headCell">Report</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php $i = 1; foreach ($reports as $r): ?>
                            <?php
                                $st = strtolower((string)($r["status"] ?? ""));
                                $cls = "pillOther";
                                if ($st === "pending") $cls = "pillWarn";
                                if (in_array($st, ["done","completed","vaccinated"], true)) $cls = "pillOk";
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r["child_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($r["vaccine_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($r["hospital_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($r["booking_date"] ?? "") ?></td>
                                <td><span class="pill <?= $cls ?>"><?= htmlspecialchars($r["status"] ?? "") ?></span></td>
                                <td>
                                    <span style="color:#666;">Not available</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="color:#666;">No previous vaccination records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
