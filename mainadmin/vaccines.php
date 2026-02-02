<?php
$pageTitle = "Vaccines";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Add Vaccine
   ========================= */
if (isset($_POST['add'])) {
    $name  = trim($_POST['name'] ?? '');
    $stock = trim($_POST['stock'] ?? '');

    if ($name !== '' && $stock !== '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO vaccines(name, stock) VALUES(?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $name, $stock);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: vaccines.php?added=1");
        exit;
    } else {
        header("Location: vaccines.php?error=1");
        exit;
    }
}

/* =========================
   Delete Vaccine
   ========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = mysqli_prepare($conn, "DELETE FROM vaccines WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $deleteId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: vaccines.php?deleted=1");
    exit;
}

/* =========================
   Fetch Vaccines
   ========================= */
$result = mysqli_query($conn, "SELECT * FROM vaccines ORDER BY id DESC");
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<style>
.vaccine-card{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
}
.table th{
    background:#f5f6fa;
}
.form-control{
    height:42px;
}
</style>

<div class="card vaccine-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="m-0">Vaccines</h4>
            <?php if (isset($_GET['added'])): ?>
                <small class="text-success">Vaccine added successfully.</small>
            <?php elseif (isset($_GET['deleted'])): ?>
                <small class="text-success">Vaccine deleted successfully.</small>
            <?php elseif (isset($_GET['error'])): ?>
                <small class="text-danger">Please fill all fields.</small>
            <?php endif; ?>
        </div>

        <span class="badge bg-primary">Total: <?= $total ?></span>
    </div>

    <form method="post" class="row g-2 align-items-end mb-4">
        <div class="col-12 col-md-5">
            <label class="form-label mb-1">Vaccine Name</label>
            <input class="form-control" name="name" placeholder="e.g., Polio" required>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Stock</label>
            <input class="form-control" name="stock" placeholder="e.g., 120" required>
        </div>

        <div class="col-12 col-md-3 d-grid">
            <button class="btn btn-success" name="add" type="submit">
                Add Vaccine
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th style="width:70px;">#</th>
                    <th>Vaccine</th>
                    <th style="width:140px;">Stock</th>
                    <th style="width:140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0): ?>
                    <?php $i=1; while ($v = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($v['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($v['stock'] ?? '') ?></td>
                            <td>
                                <a
                                  href="vaccines.php?delete=<?= (int)$v['id'] ?>"
                                  class="btn btn-danger btn-sm"
                                  onclick="return confirm('Delete this vaccine?');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-muted">
                            No vaccines added yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../base/footer.php'; ?>
