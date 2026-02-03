<?php
session_start();
$pageTitle = "Manage Association Requests";
include "../includes/db.php";

/* Hospital Authentication */
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);
$hospitalName = $_SESSION["hospital_name"] ?? "";

if ($hospitalId <= 0) {
    die("Hospital session not found. Please re-login.");
}

/* Handle Action (Approve/Reject) */
if (isset($_GET['id']) && isset($_GET['action'])) {
    $requestId = (int)$_GET['id'];
    $action = $_GET['action'];
    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

    $stmt = mysqli_prepare($conn, "UPDATE hospital_requests SET status = ? WHERE id = ? AND requested_hospital = ?");
    mysqli_stmt_bind_param($stmt, "sis", $newStatus, $requestId, $hospitalName);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manageRequests.php?msg=" . ($action === 'approve' ? 'approved' : 'rejected'));
    } else {
        header("Location: manageRequests.php?msg=error");
    }
    exit;
}

/* Fetch Pending Requests 
   NOTE: I removed p.phone to fix your SQL error. 
   I used p.email instead. If your column is named 'contact', change it below.
*/
$requests = [];
$query = "SELECT r.*, c.child_name, c.birth_date, p.parent_name, p.email 
          FROM hospital_requests r
          JOIN children c ON r.child_id = c.child_id
          JOIN parents p ON r.parent_id = p.parent_id
          WHERE r.requested_hospital = ? 
          ORDER BY r.id DESC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $hospitalName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    mysqli_stmt_close($stmt);
}
include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <h2>Parent Association Requests</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-info">Action processed successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="header">
            <h2>Requests for <strong><?= htmlspecialchars($hospitalName) ?></strong></h2>
        </div>
        <div class="body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Parent</th>
                            <th>Contact (Email)</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['child_name']) ?></td>
                            <td><?= htmlspecialchars($r['parent_name']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td>
                                <span class="badge <?= ($r['status']=='Pending') ? 'badge-warning' : 'badge-success' ?>">
                                    <?= strtoupper($r['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <?php if (strtolower($r['status']) === 'pending'): ?>
                                    <a href="manageRequests.php?id=<?= $r['id'] ?>&action=approve" class="btn btn-sm btn-success">Approve</a>
                                    <a href="manageRequests.php?id=<?= $r['id'] ?>&action=reject" class="btn btn-sm btn-danger">Reject</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>