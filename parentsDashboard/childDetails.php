<?php
session_start();

$pageTitle = "Child Details";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");

$parentId = (int)($_SESSION["parent_id"] ?? 0);

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

/* Delete child, only if belongs to parent */
if (isset($_GET["delete"])) {
    $deleteId = (int)($_GET["delete"] ?? 0);

    if ($deleteId > 0) {

        /* Optional: block delete if bookings exist */
        $hasBookings = 0;
        $stmtB = mysqli_prepare($conn, "SELECT id FROM bookings WHERE child_id = ? LIMIT 1");
        if ($stmtB) {
            mysqli_stmt_bind_param($stmtB, "i", $deleteId);
            mysqli_stmt_execute($stmtB);
            $resB = mysqli_stmt_get_result($stmtB);
            $hasBookings = ($resB && mysqli_fetch_assoc($resB)) ? 1 : 0;
            mysqli_stmt_close($stmtB);
        }

        if ($hasBookings === 1) {
            header("Location: childDetails.php?cannotdelete=1");
            exit;
        }

        $stmtDel = mysqli_prepare($conn, "DELETE FROM children WHERE child_id = ? AND parent_id = ?");
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, "ii", $deleteId, $parentId);
            mysqli_stmt_execute($stmtDel);
            mysqli_stmt_close($stmtDel);
        }

        header("Location: childDetails.php?deleted=1");
        exit;
    }
}

/* Add child */
if (isset($_POST["add_child"])) {
    $childName = trim($_POST["child_name"] ?? "");
    $birthDate = trim($_POST["birth_date"] ?? "");
    $vaccStatus = trim($_POST["vaccination_status"] ?? "Pending");

    if ($childName === "" || $birthDate === "") {
        header("Location: childDetails.php?error=1");
        exit;
    }

    $stmtAdd = mysqli_prepare($conn, "
        INSERT INTO children (parent_id, child_name, birth_date, vaccination_status)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmtAdd) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtAdd, "isss", $parentId, $childName, $birthDate, $vaccStatus);
    mysqli_stmt_execute($stmtAdd);
    mysqli_stmt_close($stmtAdd);

    header("Location: childDetails.php?added=1");
    exit;
}

/* Fetch children */
$children = [];
$stmtC = mysqli_prepare($conn, "
    SELECT child_id, child_name, birth_date, vaccination_status
    FROM children
    WHERE parent_id = ?
    ORDER BY child_id DESC
");
if ($stmtC) {
    mysqli_stmt_bind_param($stmtC, "i", $parentId);
    mysqli_stmt_execute($stmtC);
    $resC = mysqli_stmt_get_result($stmtC);
    while ($resC && ($rowC = mysqli_fetch_assoc($resC))) {
        $children[] = $rowC;
    }
    mysqli_stmt_close($stmtC);
}

include "../base/header.php";

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
?>

<style>
.cardBox{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.formBox{
    border-radius:12px;
    border:1px solid #eef0f5;
    padding:18px;
    background:#fff;
}
.tableHead{
    background:#f5f6fa;
}
.smallMsg{
    margin-top:6px;
    font-size:13px;
}
</style>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
            <h3 style="margin:0;">Child Details</h3>

            <?php if (isset($_GET["added"])): ?>
                <div class="smallMsg" style="color:#0a7a31;">Child added successfully.</div>
            <?php elseif (isset($_GET["updated"])): ?>
                <div class="smallMsg" style="color:#0a7a31;">Child updated successfully.</div>
            <?php elseif (isset($_GET["deleted"])): ?>
                <div class="smallMsg" style="color:#0a7a31;">Child deleted successfully.</div>
            <?php elseif (isset($_GET["cannotdelete"])): ?>
                <div class="smallMsg" style="color:#b00020;">Cannot delete, bookings exist for this child.</div>
            <?php elseif (isset($_GET["error"])): ?>
                <div class="smallMsg" style="color:#b00020;">Please fill required fields.</div>
            <?php endif; ?>
        </div>

        <a href="parentdashboard.php" class="btn btn-outline-primary" style="padding:8px 12px;">Back</a>
    </div>

    <div class="cardBox" style="margin-bottom:16px;">
        <div class="formBox">
            <h4 style="margin:0 0 12px 0;">Add Child</h4>

            <form method="post">
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:220px; margin-bottom:12px;">
                        <label style="display:block; margin-bottom:6px;">Child Name</label>
                        <input type="text" name="child_name" class="form-control" required>
                    </div>

                    <div style="flex:1; min-width:220px; margin-bottom:12px;">
                        <label style="display:block; margin-bottom:6px;">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control" required>
                    </div>

                    <div style="flex:1; min-width:220px; margin-bottom:12px;">
                        <label style="display:block; margin-bottom:6px;">Vaccination Status</label>
                        <select name="vaccination_status" class="form-control">
                            <option value="Pending">Pending</option>
                            <option value="Up to date">Up to date</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="add_child" class="btn btn-primary" style="padding:10px 14px;">
                    Save
                </button>
            </form>
        </div>
    </div>

    <div class="cardBox">
        <div style="overflow:auto;">
            <table class="table table-bordered" style="margin:0; text-align:center; vertical-align:middle;">
                <thead>
                    <tr>
                        <th class="tableHead" style="width:70px;">No</th>
                        <th class="tableHead">Child Name</th>
                        <th class="tableHead">Birth Date</th>
                        <th class="tableHead">Age</th>
                        <th class="tableHead">Status</th>
                        <th class="tableHead" style="width:200px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($children) > 0): ?>
                        <?php $i = 1; foreach ($children as $c): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($c["child_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($c["birth_date"] ?? "") ?></td>
                                <td><?= htmlspecialchars(calc_age_text($c["birth_date"] ?? "")) ?></td>
                                <td><?= htmlspecialchars($c["vaccination_status"] ?? "Pending") ?></td>
                                <td>
                                    <a class="btn btn-primary" style="padding:6px 10px; margin-right:6px;"
                                       href="editChild.php?id=<?= (int)($c["child_id"] ?? 0) ?>">
                                        Edit
                                    </a>

                                    <a class="btn btn-danger" style="padding:6px 10px;"
                                       href="childDetails.php?delete=<?= (int)($c["child_id"] ?? 0) ?>"
                                       onclick="return confirm('Are you sure you want to delete this child?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="color:#666;">No child records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
