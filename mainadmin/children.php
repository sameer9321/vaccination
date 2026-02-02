<?php
$pageTitle = "All Children";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Delete child (same page)
   ========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    // Delete by child_id
    $stmt = mysqli_prepare($conn, "DELETE FROM children WHERE child_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $deleteId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect to avoid re-delete on refresh
    header("Location: children.php?deleted=1");
    exit;
}

/* =========================
   Fetch Children + Parent
   ========================= */
$result = mysqli_query($conn, "
    SELECT 
        c.child_id,
        c.child_name,
        c.birth_date,
        c.vaccination_status,
        p.parent_name
    FROM children c
    LEFT JOIN parents p ON p.parent_id = c.parent_id
    ORDER BY c.child_id DESC
");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<style>
.child-card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:25px;
}
.table th{
    background:#f5f6fa;
}
.status-badge{
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.status-pending{ background:#fff3cd; color:#856404; }
.status-done{ background:#d4edda; color:#155724; }
.status-other{ background:#e2e3e5; color:#383d41; }
.action-btns .btn{
    padding:6px 10px;
    font-size:13px;
}
</style>

<div class="card child-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="m-0">All Children</h4>
            <?php if (isset($_GET['deleted'])): ?>
                <small class="text-success">Child record deleted successfully.</small>
            <?php endif; ?>
        </div>
        <span class="badge bg-primary">
            Total: <?= mysqli_num_rows($result); ?>
        </span>
    </div>

    <table class="table table-bordered table-hover text-center align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Child Name</th>
                <th>Date of Birth</th>
                <th>Parent Name</th>
                <th>Vaccination Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php
        $i = 1;
        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):

                $statusRaw = $row['vaccination_status'] ?? 'Pending';
                $status = trim((string)$statusRaw);

                // Basic badge class mapping
                $badgeClass = 'status-other';
                if (strtolower($status) === 'pending') $badgeClass = 'status-pending';
                if (in_array(strtolower($status), ['done', 'completed', 'vaccinated'], true)) $badgeClass = 'status-done';
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['child_name']) ?></td>
                <td><?= htmlspecialchars($row['birth_date']) ?></td>
                <td><?= htmlspecialchars($row['parent_name'] ?? 'Not Assigned') ?></td>
                <td>
                    <span class="status-badge <?= $badgeClass ?>">
                        <?= htmlspecialchars($status ?: 'Pending') ?>
                    </span>
                </td>
                <td class="action-btns">
                    <a class="btn btn-sm btn-warning"
                       href="child_edit.php?id=<?= (int)$row['child_id'] ?>">
                        Edit
                    </a>

                    <a class="btn btn-sm btn-danger"
                       href="children.php?delete=<?= (int)$row['child_id'] ?>"
                       onclick="return confirm('Are you sure you want to delete this child record?');">
                        Delete
                    </a>
                </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="6" class="text-muted text-center">
                    No child records found.
                </td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

<?php include '../base/footer.php'; ?>
