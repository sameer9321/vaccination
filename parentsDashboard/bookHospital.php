<?php
session_start();
$pageTitle = "Book Hospital";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "Parent");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parentId from parents table if missing */
if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    if ($rowU) {
        $email = $rowU['email'];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $email);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = $rowP['parent_id'];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) { die("Parent not linked. Please re-login."); }

if (isset($_GET["delete"])) {
    $deleteId = (int)$_GET["delete"];
    mysqli_query($conn, "DELETE b FROM bookings b JOIN children c ON c.child_id = b.child_id WHERE b.id = $deleteId AND c.parent_id = $parentId");
    header("Location: bookHospital.php?deleted=1");
    exit;
}

if (isset($_POST["book"])) {
    $childId = (int)$_POST["child_id"];
    $hospitalId = (int)$_POST["hospital_id"];
    $vaccineName = mysqli_real_escape_string($conn, $_POST["vaccine_name"]);
    $bookingDate = $_POST["booking_date"];

    if ($bookingDate < date("Y-m-d")) {
        header("Location: bookHospital.php?past=1");
    } else {
        $stmtAdd = mysqli_prepare($conn, "INSERT INTO bookings (child_id, hospital_id, vaccine_name, booking_date, status) VALUES (?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmtAdd, "iiss", $childId, $hospitalId, $vaccineName, $bookingDate);
        mysqli_stmt_execute($stmtAdd);
        header("Location: bookHospital.php?booked=1");
    }
    exit;
}

$children = mysqli_query($conn, "SELECT child_id, child_name FROM children WHERE parent_id = $parentId ORDER BY child_name ASC");
$hospitals = mysqli_query($conn, "SELECT id, hospital_name FROM hospitals ORDER BY hospital_name ASC");
$bookings = mysqli_query($conn, "SELECT b.*, c.child_name, h.hospital_name FROM bookings b JOIN children c ON c.child_id = b.child_id JOIN hospitals h ON h.id = b.hospital_id WHERE c.parent_id = $parentId ORDER BY b.booking_date DESC");

include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Book Vaccination</h2>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Hospital Booking</li>
                </ul>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 text-right">
                <a href="parentdashboard.php" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET["booked"])): ?>
        <div class="alert alert-success">Appointment booked successfully!</div>
    <?php elseif (isset($_GET["past"])): ?>
        <div class="alert alert-danger">Error: Please select a current or future date.</div>
    <?php elseif (isset($_GET["deleted"])): ?>
        <div class="alert alert-warning">Booking has been cancelled.</div>
    <?php endif; ?>

    <div class="row clearfix">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>New</strong> Appointment</h2>
                </div>
                <div class="body">
                    <?php if (mysqli_num_rows($children) === 0): ?>
                        <div class="alert alert-info">Please <a href="childDetails.php">add a child</a> first.</div>
                    <?php else: ?>
                    <form method="post">
                        <div class="form-group mb-3">
                            <label>Select Child</label>
                            <select name="child_id" class="form-control" required>
                                <option value="">-- Choose Child --</option>
                                <?php while($c = mysqli_fetch_assoc($children)): ?>
                                    <option value="<?= $c['child_id'] ?>"><?= htmlspecialchars($c['child_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Select Hospital</label>
                            <select name="hospital_id" class="form-control" required>
                                <option value="">-- Choose Hospital --</option>
                                <?php while($h = mysqli_fetch_assoc($hospitals)): ?>
                                    <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['hospital_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Vaccine Name</label>
                            <input type="text" name="vaccine_name" class="form-control" placeholder="e.g. Hepatitis B, Polio" required>
                        </div>
                        <div class="form-group mb-4">
                            <label>Preferred Date</label>
                            <input type="date" name="booking_date" class="form-control" required>
                        </div>
                        <button type="submit" name="book" class="btn btn-primary btn-round btn-block">Confirm Booking</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Booking</strong> History</h2>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Child</th>
                                    <th>Vaccine</th>
                                    <th>Hospital</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i=1; 
                                if(mysqli_num_rows($bookings) > 0): 
                                    while($b = mysqli_fetch_assoc($bookings)): 
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= htmlspecialchars($b['child_name']) ?></strong></td>
                                    <td><span class="badge bg-blue text-white"><?= htmlspecialchars($b['vaccine_name']) ?></span></td>
                                    <td><?= htmlspecialchars($b['hospital_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                    <td>
                                        <?php 
                                            $s = strtolower($b['status']);
                                            $badge = "badge-warning";
                                            if($s == 'vaccinated' || $s == 'completed') $badge = "badge-success";
                                            if($s == 'rejected') $badge = "badge-danger";
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= strtoupper($b['status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="bookHospital.php?delete=<?= $b['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Cancel this appointment?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No appointments found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>