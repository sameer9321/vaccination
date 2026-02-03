<?php
$pageTitle = "Parent Requests";
include '../base/header.php';
include '../includes/db.php';

/* ===== Approve / Reject Logic ===== */
if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);
    // Updated table name to match your SQL file
    mysqli_query($conn,"UPDATE hospital_requests SET status='Approved' WHERE id=$id");
    header("Location: requests.php?msg=approved");
    exit;
}

if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);
    // Updated table name to match your SQL file
    mysqli_query($conn,"UPDATE hospital_requests SET status='Rejected' WHERE id=$id");
    header("Location: requests.php?msg=rejected");
    exit;
}

/* ===== Fetch Data using JOIN to get Parent Names ===== */
// Your SQL file uses 'hospital_requests'. We JOIN with 'parents' to get the actual name.
$query = "SELECT hr.*, p.parent_name 
          FROM hospital_requests hr 
          JOIN parents p ON hr.parent_id = p.parent_id 
          WHERE hr.status='Pending' 
          ORDER BY hr.id DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
?>

<style>
.request-card {
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    padding: 25px;
    background: #fff;
}
.table th { background: #f8f9fa; color: #333; }
.badge-pending { background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; }
.btn-action { transition: 0.3s; }
.btn-action:hover { transform: scale(1.05); }
</style>

<div class="container py-4">
    <div class="card request-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0"><i class="fa fa-envelope-open text-primary me-2"></i> Pending Parent Requests</h4>
            <span class="badge bg-danger p-2">
                Total Pending: <?php echo mysqli_num_rows($result); ?>
            </span>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Request successfully <?php echo htmlspecialchars($_GET['msg']); ?>!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover border align-middle text-center">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Parent Name</th>
                        <th>Hospital Requested</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $i = 1;
                if(mysqli_num_rows($result) > 0):
                    while($r = mysqli_fetch_assoc($result)):
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['parent_name']) ?></td>
                        <td>
                            <small class="text-muted">Request ID: #<?= $r['id'] ?></small><br>
                            <span class="badge bg-light text-dark border">Hospital ID: <?= $r['hospital_id'] ?></span>
                        </td>
                        <td><span class="badge-pending">Pending</span></td>
                        <td>
                            <div class="btn-group">
                                <a href="?approve=<?= $r['id'] ?>" class="btn btn-success btn-sm btn-action me-2">
                                    <i class="fa fa-check"></i> Approve
                                </a>
                                <a href="?reject=<?= $r['id'] ?>" class="btn btn-danger btn-sm btn-action" 
                                   onclick="return confirm('Are you sure you want to reject this request?')">
                                    <i class="fa fa-times"></i> Reject
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="5" class="py-5 text-muted">
                            <i class="fa fa-folder-open-o fa-3x mb-3"></i><br>
                            <h5>No pending requests found</h5>
                            <p>All parent requests have been processed.</p>
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../base/footer.php'; ?>