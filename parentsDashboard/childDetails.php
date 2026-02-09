<?php
session_start();
$pageTitle = "Child Details";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "Parent");
$parentId = (int)($_SESSION["parent_id"] ?? 0);

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
    $checkB = mysqli_query($conn, "SELECT id FROM bookings WHERE child_id = $deleteId LIMIT 1");
    if (mysqli_num_rows($checkB) > 0) {
        header("Location: childDetails.php?cannotdelete=1");
    } else {
        mysqli_query($conn, "DELETE FROM children WHERE child_id = $deleteId AND parent_id = $parentId");
        header("Location: childDetails.php?deleted=1");
    }
    exit;
}

if (isset($_POST["add_child"])) {
    $childName = mysqli_real_escape_string($conn, $_POST["child_name"]);
    $birthDate = $_POST["birth_date"];
    $vaccStatus = $_POST["vaccination_status"];

    $stmtAdd = mysqli_prepare($conn, "INSERT INTO children (parent_id, child_name, birth_date, vaccination_status) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmtAdd, "isss", $parentId, $childName, $birthDate, $vaccStatus);
    mysqli_stmt_execute($stmtAdd);
    header("Location: childDetails.php?added=1");
    exit;
}

$childrenResult = mysqli_query($conn, "SELECT * FROM children WHERE parent_id = $parentId ORDER BY child_id DESC");

include "../base/header.php";

function calc_age_text($birthDate) {
    if (!$birthDate) return "N/A";
    $dob = new DateTime($birthDate);
    $now = new DateTime();
    $diff = $now->diff($dob);
    if ($diff->y > 0) return $diff->y . " yrs";
    if ($diff->m > 0) return $diff->m . " months";
    return $diff->d . " days";
}
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Child Details</h2>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Manage Children</li>
                </ul>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 text-right">
                <a href="parentdashboard.php" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET["added"])): ?>
        <div class="alert alert-success">Child profile added successfully!</div>
    <?php elseif (isset($_GET["deleted"])): ?>
        <div class="alert alert-warning">Child record removed.</div>
    <?php elseif (isset($_GET["cannotdelete"])): ?>
        <div class="alert alert-danger">Cannot delete: This child has active vaccine bookings.</div>
    <?php endif; ?>

    <div class="row clearfix">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Add New</strong> Child</h2>
                </div>
                <div class="body">
                    <form method="post">
                        <div class="form-group">
                            <label>Child Full Name</label>
                            <input type="text" name="child_name" class="form-control" placeholder="Enter name" required>
                        </div>
                        <div class="form-group">
                            <label>Birth Date</label>
                            <input type="date" name="birth_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Initial Status</label>
                            <select name="vaccination_status" class="form-control">
                                <option value="Pending">Pending</option>
                                <option value="Up to date">Up to date</option>
                            </select>
                        </div>
                        <button type="submit" name="add_child" class="btn btn-primary btn-round btn-block">Register Child</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Registered</strong> Children</h2>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0">
                            <thead>
                                <tr>
                                    <th>Child Name</th>
                                    <th>Birth Date</th>
                                    <th>Age</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($childrenResult) > 0): while($c = mysqli_fetch_assoc($childrenResult)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['child_name']) ?></strong></td>
                                    <td><?= date('d M Y', strtotime($c['birth_date'])) ?></td>
                                    <td><span class="badge badge-info"><?= calc_age_text($c['birth_date']) ?></span></td>
                                    <td>
                                        <?php if($c['vaccination_status'] == 'Completed'): ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= $c['vaccination_status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="editChild.php?id=<?= $c['child_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                        <a href="childDetails.php?delete=<?= $c['child_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Delete this record?');" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No child records found. Add one on the left.</td>
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