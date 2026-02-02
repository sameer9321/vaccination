<?php
session_start();

$pageTitle = "Book Hospital";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parentId from parents table if missing */
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

/* Delete booking (only if booking belongs to this parent) */
if (isset($_GET["delete"])) {
    $deleteId = (int)($_GET["delete"] ?? 0);

    if ($deleteId > 0) {
        $stmtDel = mysqli_prepare($conn, "
            DELETE b
            FROM bookings b
            JOIN children c ON c.child_id = b.child_id
            WHERE b.id = ? AND c.parent_id = ?
        ");
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, "ii", $deleteId, $parentId);
            mysqli_stmt_execute($stmtDel);
            mysqli_stmt_close($stmtDel);
        }

        header("Location: bookHospital.php?deleted=1");
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

/* Fetch hospitals for dropdown */
$hospitals = [];
$resH = mysqli_query($conn, "SELECT id, hospital_name FROM hospitals ORDER BY hospital_name ASC");
if ($resH) {
    while ($rowH = mysqli_fetch_assoc($resH)) {
        $hospitals[] = $rowH;
    }
}

/* Add booking */
if (isset($_POST["book"])) {

    $childId = (int)($_POST["child_id"] ?? 0);
    $hospitalId = (int)($_POST["hospital_id"] ?? 0);
    $vaccineName = trim($_POST["vaccine_name"] ?? "");
    $bookingDate = trim($_POST["booking_date"] ?? "");

    if ($childId <= 0 || $hospitalId <= 0 || $vaccineName === "" || $bookingDate === "") {
        header("Location: bookHospital.php?error=1");
        exit;
    }

    /* Verify child belongs to this parent */
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
        header("Location: bookHospital.php?error=1");
        exit;
    }

    /* Prevent past date */
    if ($bookingDate < date("Y m d")) {
        header("Location: bookHospital.php?past=1");
        exit;
    }

    $status = "Pending";

    $stmtAdd = mysqli_prepare($conn, "
        INSERT INTO bookings (child_id, hospital_id, vaccine_name, booking_date, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmtAdd) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmtAdd, "iisss", $childId, $hospitalId, $vaccineName, $bookingDate, $status);
    mysqli_stmt_execute($stmtAdd);
    mysqli_stmt_close($stmtAdd);

    header("Location: bookHospital.php?booked=1");
    exit;
}

/* Fetch bookings for this parent */
$bookings = [];
$stmtB = mysqli_prepare($conn, "
    SELECT
        b.id,
        c.child_name,
        h.hospital_name,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ?
    ORDER BY b.booking_date DESC
");
if ($stmtB) {
    mysqli_stmt_bind_param($stmtB, "i", $parentId);
    mysqli_stmt_execute($stmtB);
    $resB = mysqli_stmt_get_result($stmtB);
    while ($resB && ($rowB = mysqli_fetch_assoc($resB))) {
        $bookings[] = $rowB;
    }
    mysqli_stmt_close($stmtB);
}

include "../base/header.php";
?>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin:0 0 14px 0;">
        <div>
            <h3 style="margin:0;">Book Hospital</h3>

            <?php if (isset($_GET["booked"])): ?>
                <div style="color:#0a7a31; margin:6px 0 0 0;">Booking created successfully.</div>
            <?php elseif (isset($_GET["deleted"])): ?>
                <div style="color:#0a7a31; margin:6px 0 0 0;">Booking deleted successfully.</div>
            <?php elseif (isset($_GET["past"])): ?>
                <div style="color:#b00020; margin:6px 0 0 0;">Please select today or a future date.</div>
            <?php elseif (isset($_GET["error"])): ?>
                <div style="color:#b00020; margin:6px 0 0 0;">Please fill all fields correctly.</div>
            <?php endif; ?>
        </div>

        <a href="parentdashboard.php" class="btn" style="border:1px solid #0d6efd; color:#0d6efd; padding:8px 12px; text-decoration:none;">
            Back
        </a>
    </div>

    <div class="card">
        <div class="body">
            <h4 style="margin:0 0 12px 0;">Create Booking</h4>

            <?php if (count($children) === 0): ?>
                <div style="color:#b00020; margin:0 0 12px 0;">
                    No child found. Please add a child first from Child Details.
                </div>
            <?php endif; ?>

            <form method="post">
                <div style="display:flex; gap:12px; flex-wrap:wrap;">

                    <div style="flex:1; min-width:220px; margin:0 0 12px 0;">
                        <label style="display:block; margin:0 0 6px 0;">Child</label>
                        <select name="child_id" class="form-control" required>
                            <option value="">Select child</option>
                            <?php foreach ($children as $c): ?>
                                <option value="<?= (int)$c["child_id"] ?>">
                                    <?= htmlspecialchars($c["child_name"] ?? "") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex:1; min-width:220px; margin:0 0 12px 0;">
                        <label style="display:block; margin:0 0 6px 0;">Hospital</label>
                        <select name="hospital_id" class="form-control" required>
                            <option value="">Select hospital</option>
                            <?php foreach ($hospitals as $h): ?>
                                <option value="<?= (int)$h["id"] ?>">
                                    <?= htmlspecialchars($h["hospital_name"] ?? "") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex:1; min-width:220px; margin:0 0 12px 0;">
                        <label style="display:block; margin:0 0 6px 0;">Vaccine name</label>
                        <input type="text" name="vaccine_name" class="form-control" placeholder="Example Polio" required>
                    </div>

                    <div style="flex:1; min-width:220px; margin:0 0 12px 0;">
                        <label style="display:block; margin:0 0 6px 0;">Booking date</label>
                        <input type="date" name="booking_date" class="form-control" required>
                    </div>

                </div>

                <button type="submit" name="book" class="btn" style="background:#0d6efd; color:#fff; padding:10px 14px; border:0;">
                    Book
                </button>
            </form>
        </div>
    </div>

    <div style="margin:16px 0 0 0;"></div>

    <div class="card">
        <div class="body">
            <div style="display:flex; justify-content:space-between; align-items:center; margin:0 0 12px 0;">
                <h4 style="margin:0;">My Bookings</h4>
                <div style="background:#0d6efd; color:#fff; padding:6px 10px; border-radius:16px;">
                    Total: <?= count($bookings) ?>
                </div>
            </div>

            <div style="overflow:auto;">
                <table class="table" style="width:100%; text-align:center;">
                    <thead>
                        <tr>
                            <th style="background:#f5f6fa;">No</th>
                            <th style="background:#f5f6fa;">Child</th>
                            <th style="background:#f5f6fa;">Hospital</th>
                            <th style="background:#f5f6fa;">Vaccine</th>
                            <th style="background:#f5f6fa;">Date</th>
                            <th style="background:#f5f6fa;">Status</th>
                            <th style="background:#f5f6fa;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php $i = 1; foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($b["child_name"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($b["hospital_name"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($b["vaccine_name"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($b["booking_date"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($b["status"] ?? "Pending") ?></td>
                                    <td>
                                        <a class="btn"
                                           style="background:#dc3545; color:#fff; padding:6px 10px; text-decoration:none;"
                                           href="bookHospital.php?delete=<?= (int)$b["id"] ?>"
                                           onclick="return confirm('Delete this booking?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="color:#666;">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
