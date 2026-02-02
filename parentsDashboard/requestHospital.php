<?php
session_start();

$pageTitle = "Request Hospital";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parentId if missing */
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

/* Delete request (only if belongs to this parent) */
if (isset($_GET["delete"])) {
    $deleteId = (int)($_GET["delete"] ?? 0);

    if ($deleteId > 0) {
        $stmtDel = mysqli_prepare($conn, "DELETE FROM hospital_requests WHERE id = ? AND parent_id = ?");
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, "ii", $deleteId, $parentId);
            mysqli_stmt_execute($stmtDel);
            mysqli_stmt_close($stmtDel);
        }
        header("Location: requestHospital.php?deleted=1");
        exit;
    }
}

/* Fetch children for dropdown */
$children = [];
$stmtC = mysqli_prepare($conn, "SELECT child_id, child_name FROM children WHERE parent_id = ? ORDER BY child_name ASC");
if ($stmtC) {
    mysqli_stmt_bind_param($stmtC, "i", $parentId);
    mysqli_stmt_execute($stmtC);
    $resC = mysqli_stmt_get_result($stmtC);
    while ($resC && ($rowC = mysqli_fetch_assoc($resC))) {
        $children[] = $rowC;
    }
    mysqli_stmt_close($stmtC);
}

/* Submit request */
if (isset($_POST["submit_request"])) {

    $childId = (int)($_POST["child_id"] ?? 0);
    $requestedHospital = trim($_POST["requested_hospital"] ?? "");

    if ($childId <= 0 || $requestedHospital === "") {
        header("Location: requestHospital.php?error=1");
        exit;
    }

    /* verify child belongs to parent */
    $okChild = 0;
    $stmtChk = mysqli_prepare($conn, "SELECT child_id FROM children WHERE child_id = ? AND parent_id = ? LIMIT 1");
    if ($stmtChk) {
        mysqli_stmt_bind_param($stmtChk, "ii", $childId, $parentId);
        mysqli_stmt_execute($stmtChk);
        $resChk = mysqli_stmt_get_result($stmtChk);
        $okChild = ($resChk && mysqli_fetch_assoc($resChk)) ? 1 : 0;
        mysqli_stmt_close($stmtChk);
    }

    if ($okChild !== 1) {
        header("Location: requestHospital.php?error=1");
        exit;
    }

    $status = "Pending";

    $stmtAdd = mysqli_prepare($conn, "
        INSERT INTO hospital_requests (parent_id, child_id, requested_hospital, status)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmtAdd) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtAdd, "iiss", $parentId, $childId, $requestedHospital, $status);
    mysqli_stmt_execute($stmtAdd);
    mysqli_stmt_close($stmtAdd);

    header("Location: requestHospital.php?sent=1");
    exit;
}

/* Fetch previous requests */
$requests = [];
$stmtR = mysqli_prepare($conn, "
    SELECT
        r.id,
        c.child_name,
        r.requested_hospital,
        r.status,
        r.created_at
    FROM hospital_requests r
    JOIN children c ON c.child_id = r.child_id
    WHERE r.parent_id = ?
    ORDER BY r.id DESC
");
if ($stmtR) {
    mysqli_stmt_bind_param($stmtR, "i", $parentId);
    mysqli_stmt_execute($stmtR);
    $resR = mysqli_stmt_get_result($stmtR);
    while ($resR && ($rowR = mysqli_fetch_assoc($resR))) {
        $requests[] = $rowR;
    }
    mysqli_stmt_close($stmtR);
}

include "../base/header.php";
?>

<style>
.cardBox{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
    margin-bottom:16px;
}
.tableHead{
    background:#f5f6fa;
}
.msgOk{ color:#0a7a31; font-size:13px; margin-top:6px; }
.msgErr{ color:#b00020; font-size:13px; margin-top:6px; }
.btnLike{
    display:inline-block;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    border:1px solid #0d6efd;
    color:#0d6efd;
}
.btnPrimaryLike{
    display:inline-block;
    padding:10px 14px;
    border-radius:8px;
    text-decoration:none;
    border:0;
    background:#0d6efd;
    color:#fff;
}
.btnDangerLike{
    display:inline-block;
    padding:7px 10px;
    border-radius:8px;
    text-decoration:none;
    border:0;
    background:#dc3545;
    color:#fff;
}
</style>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
            <h3 style="margin:0;">Request Hospital</h3>

            <?php if (isset($_GET["sent"])): ?>
                <div class="msgOk">Request submitted successfully.</div>
            <?php elseif (isset($_GET["deleted"])): ?>
                <div class="msgOk">Request deleted successfully.</div>
            <?php elseif (isset($_GET["error"])): ?>
                <div class="msgErr">Please fill all fields correctly.</div>
            <?php endif; ?>
        </div>

        <a class="btnLike" href="parentdashboard.php">Back</a>
    </div>

    <div class="cardBox">
        <h4 style="margin:0 0 12px 0;">Submit Request</h4>

        <?php if (count($children) === 0): ?>
            <div class="msgErr">No child found. Please add a child first.</div>
        <?php endif; ?>

        <form method="post">
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div style="flex:1; min-width:220px; margin-bottom:12px;">
                    <label style="display:block; margin-bottom:6px;">Child</label>
                    <select name="child_id" class="form-control" required>
                        <option value="">Select child</option>
                        <?php foreach ($children as $c): ?>
                            <option value="<?= (int)$c["child_id"] ?>">
                                <?= htmlspecialchars($c["child_name"] ?? "") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1; min-width:220px; margin-bottom:12px;">
                    <label style="display:block; margin-bottom:6px;">Hospital request</label>
                    <input type="text" name="requested_hospital" class="form-control" placeholder="Enter hospital name" required>
                </div>
            </div>

            <button type="submit" name="submit_request" class="btnPrimaryLike">
                Submit Request
            </button>
        </form>
    </div>

    <div class="cardBox">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h4 style="margin:0;">Previous Requests</h4>
            <div style="background:#0d6efd; color:#fff; padding:6px 10px; border-radius:16px;">
                Total: <?= count($requests) ?>
            </div>
        </div>

        <div style="overflow:auto;">
            <table class="table table-bordered" style="width:100%; text-align:center; margin:0;">
                <thead>
                    <tr>
                        <th class="tableHead" style="width:70px;">No</th>
                        <th class="tableHead">Child</th>
                        <th class="tableHead">Requested hospital</th>
                        <th class="tableHead">Status</th>
                        <th class="tableHead">Date</th>
                        <th class="tableHead" style="width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php $i = 1; foreach ($requests as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r["child_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($r["requested_hospital"] ?? "") ?></td>
                                <td><?= htmlspecialchars($r["status"] ?? "Pending") ?></td>
                                <td><?= htmlspecialchars($r["created_at"] ?? "") ?></td>
                                <td>
                                    <a class="btnDangerLike"
                                       href="requestHospital.php?delete=<?= (int)$r["id"] ?>"
                                       onclick="return confirm('Delete this request?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="color:#666;">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include "../base/footer.php"; ?>
