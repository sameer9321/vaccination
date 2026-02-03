<?php
$pageTitle = "All Children";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Delete child logic
   ========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    // Using a transaction ensures if one delete fails, none happen
    mysqli_begin_transaction($conn);
    try {
        // Delete related bookings first to avoid foreign key errors
        mysqli_query($conn, "DELETE FROM bookings WHERE child_id = $deleteId");
        
        // Delete the child
        $stmt = mysqli_prepare($conn, "DELETE FROM children WHERE child_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $deleteId);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        header("Location: children.php?deleted=1");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: children.php?error=1");
    }
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
?>

<style>
    .child-card { border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,0.08); padding:25px; background:#fff; }
    .status-badge { padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-pending { background:#fff3cd; color:#856404; }
    .status-done { background:#d4edda; color:#155724; }
    .status-other { background:#e2e3e5; color:#383d41; }
    .search-box { max-width: 300px; border-radius: 20px; }
</style>

<div class="container py-4">
    <div class="card child-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="m-0"><i class="fa fa-child text-primary me-2"></i>Child Management</h4>
                <?php if (isset($_GET['deleted'])): ?>
                    <span class="badge bg-success mt-2">Record removed successfully</span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center">
                <input type="text" id="childSearch" class="form-control search-box me-3" placeholder="Search by name...">
                <span class="badge bg-primary p-2">Total: <?= mysqli_num_rows($result); ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover border align-middle text-center" id="childrenTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Child Name</th>
                        <th>Date of Birth</th>
                        <th>Parent Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        $status = strtolower(trim($row['vaccination_status'] ?? 'pending'));
                        $badgeClass = ($status === 'pending') ? 'status-pending' : 
                                      (in_array($status, ['done','completed','vaccinated']) ? 'status-done' : 'status-other');
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['child_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($row['birth_date'])) ?></td>
                        <td><i class="fa fa-user-circle-o me-1"></i> <?= htmlspecialchars($row['parent_name'] ?? 'Guest') ?></td>
                        <td>
                            <span class="status-badge <?= $badgeClass ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="child_edit.php?id=<?= $row['child_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <a href="children.php?delete=<?= $row['child_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Deleting this child will also remove their booking history. Continue?');">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No child records found in the system.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Real-time search functionality
document.getElementById('childSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#childrenTable tbody tr');
    
    rows.forEach(row => {
        let name = row.cells[1].textContent.toLowerCase();
        row.style.display = name.includes(filter) ? '' : 'none';
    });
});
</script>

<?php include '../base/footer.php'; ?>