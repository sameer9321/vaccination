<?php
$pageTitle = "Parent Requests";
include '../base/header.php';
include '../includes/db.php';


/* ===== Approve / Reject Logic ===== */
if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);
    mysqli_query($conn,"UPDATE requests SET status='approved' WHERE id=$id");
    header("Location: requests.php");
    exit;
}

if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);
    mysqli_query($conn,"UPDATE requests SET status='rejected' WHERE id=$id");
    header("Location: requests.php");
    exit;
}


/* ===== Fetch Data ===== */
$result = mysqli_query($conn,"SELECT * FROM requests WHERE status='pending'");
?>

<style>
.request-card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:25px;
}
.table th{
    background:#f5f6fa;
}
.badge-pending{
    background:#ffc107;
}
</style>


<div class="card request-card">

    <div class="d-flex justify-content-between mb-3">
        <h4 class="m-0">Parent Requests</h4>
        <span class="badge bg-danger">
            Pending: <?php echo mysqli_num_rows($result); ?>
        </span>
    </div>


    <table class="table table-bordered table-hover text-center">
        <thead>
            <tr>
                <th>#</th>
                <th>Parent Name</th>
                <th>Message</th>
                <th>Status</th>
                <th width="200">Action</th>
            </tr>
        </thead>
        <tbody>

        <?php
        $i=1;
        if(mysqli_num_rows($result) > 0):
            while($r=mysqli_fetch_assoc($result)):
        ?>

            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['parent_name']) ?></td>
                <td><?= htmlspecialchars($r['message']) ?></td>
                <td><span class="badge badge-pending">Pending</span></td>
                <td>
                    <a href="?approve=<?= $r['id'] ?>" class="btn btn-success btn-sm">
                        Approve
                    </a>
                    <a href="?reject=<?= $r['id'] ?>" class="btn btn-danger btn-sm">
                        Reject
                    </a>
                </td>
            </tr>

        <?php
            endwhile;
        else:
        ?>

            <tr>
                <td colspan="5" class="text-center text-muted">
                    No pending requests
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>

</div>


<?php include '../base/footer.php'; ?>
