<?php
session_start();
$pageTitle = "Request Hospital";
include "../includes/db.php";

// Access Control
if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int) ($_SESSION["user_id"] ?? 0);
$username = (string) ($_SESSION["username"] ?? "");
$parentId = (int) ($_SESSION["parent_id"] ?? 0);

/* Resolve parentId if missing */
if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    if ($rowU) {
        $userEmail = $rowU["email"];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $userEmail);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = (int) $rowP["parent_id"];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please re-login.");
}

/* Delete request logic */
if (isset($_GET["delete"])) {
    $deleteId = (int) $_GET["delete"];
    $stmtDel = mysqli_prepare($conn, "DELETE FROM hospital_requests WHERE id = ? AND parent_id = ?");
    mysqli_stmt_bind_param($stmtDel, "ii", $deleteId, $parentId);
    mysqli_stmt_execute($stmtDel);
    mysqli_stmt_close($stmtDel);
    header("Location: requestHospitals.php?deleted=1");
    exit;
}

/* Submit request logic */
$success = false;

if (isset($_POST["submit_request"])) {
    $childId = (int) $_POST["child_id"];
    $requestedHospital = trim($_POST["requested_hospital"]);

    if ($childId > 0 && !empty($requestedHospital)) {
        $status = "Pending";

        $stmtAdd = mysqli_prepare(
            $conn,
            "INSERT INTO hospital_requests (parent_id, child_id, requested_hospital, status)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmtAdd, "iiss", $parentId, $childId, $requestedHospital, $status);
        mysqli_stmt_execute($stmtAdd);
        mysqli_stmt_close($stmtAdd);

        // success flag (NO REDIRECT)
        $success = true;
    }
}


/* Fetch Children */
$children = [];
$resC = mysqli_query($conn, "SELECT child_id, child_name FROM children WHERE parent_id = $parentId ORDER BY child_name ASC");
while ($rowC = mysqli_fetch_assoc($resC))
    $children[] = $rowC;

/* Fetch Hospitals (Optional: If you want a dropdown instead of text input) */
$hospitalList = mysqli_query($conn, "SELECT hospital_name FROM hospitals ORDER BY hospital_name ASC");

/* Fetch Requests */
$requests = [];
$resR = mysqli_query($conn, "SELECT r.*, c.child_name FROM hospital_requests r JOIN children c ON c.child_id = r.child_id WHERE r.parent_id = $parentId ORDER BY r.id DESC");
while ($rowR = mysqli_fetch_assoc($resR))
    $requests[] = $rowR;

include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Hospital Association <small>Request to link your child to a specific hospital</small></h2>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i>
                            Dashboard</a></li>
                    <li class="breadcrumb-item active">Hospital Requests</li>
                </ul>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 text-right">
                <a href="parentdashboard.php" class="btn btn-sm btn-outline-primary btn-round">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>New</strong> Request</h2>
                </div>
                <div class="body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Your request has been sent.
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label>Select Child</label>
                            <select name="child_id" class="form-control custom-select" required>
                                <option value="">-- Choose Child --</option>
                                <?php foreach ($children as $c): ?>
                                    <option value="<?= $c['child_id'] ?>"><?= htmlspecialchars($c['child_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label>Requested Hospital</label>
                            <input type="text" name="requested_hospital" class="form-control"
                                placeholder="Type hospital name..." required>
                        </div>

                        <button type="submit" name="submit_request" class="btn btn-primary btn-block btn-round mt-4">
                            <i class="fa fa-paper-plane"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Request</strong> History</h2>
                    <ul class="header-dropdown">
                        <li><span class="badge badge-info"><?= count($requests) ?> Total</span></li>
                    </ul>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Child Name</th>
                                    <th>Hospital</th>
                                    <th>Status</th>
                                    <th>Submitted On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($requests) > 0): ?>
                                    <?php foreach ($requests as $r): ?>
                                        <?php
                                        $st = strtolower($r['status']);
                                        $badge = "badge-warning";
                                        if ($st == 'approved' || $st == 'accepted')
                                            $badge = "badge-success";
                                        if ($st == 'rejected')
                                            $badge = "badge-danger";
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($r["child_name"]) ?></strong></td>
                                            <td><?= htmlspecialchars($r["requested_hospital"]) ?></td>
                                            <td><span class="badge <?= $badge ?> text-uppercase"><?= $r["status"] ?></span></td>
                                            <td><small><?= date('M d, Y', strtotime($r["created_at"])) ?></small></td>
                                            <td>
                                                <a href="requestHospitals.php?delete=<?= $r['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger" title="Cancel Request"
                                                    onclick="return confirm('Are you sure you want to cancel this request?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa fa-folder-open-o fa-3x d-block mb-2"></i>
                                            No association requests found.
                                        </td>
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