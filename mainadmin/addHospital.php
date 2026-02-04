<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Hospitals";
include '../base/header.php';
include '../includes/db.php';

/* =========================
   Delete Hospital
   ========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = mysqli_prepare($conn, "DELETE FROM hospitals WHERE id = ?");
    if (!$stmt) {
        die("Prepare Failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $deleteId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect after deletion
    header("Location: addHospital.php?deleted=1");
    exit;
}

/* =========================
   Add Hospital
   ========================= */
if (isset($_POST['add'])) {
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');

    if ($hospital_name !== '' && $address !== '' && $phone !== '') {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO hospitals (hospital_name, address, phone) VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            die("Prepare Failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "sss", $hospital_name, $address, $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Redirect after adding
        header("Location: addHospital.php?added=1");
        exit;

    } else {
        header("Location: addHospital.php?error=1");
        exit;
    }
}

/* =========================
   Fetch Hospitals
   ========================= */
$result = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY id DESC");
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<style>
.hospital-card{
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

<div class="card hospital-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="m-0">Hospitals</h4>

            <?php if (isset($_GET['added'])): ?>
                <small class="text-success">Hospital added successfully.</small>
            <?php elseif (isset($_GET['deleted'])): ?>
                <small class="text-success">Hospital deleted successfully.</small>
            <?php elseif (isset($_GET['error'])): ?>
                <small class="text-danger">Please fill all fields.</small>
            <?php endif; ?>
        </div>

        <span class="badge bg-primary">Total: <?= $total ?></span>
    </div>

    <!-- Add Hospital Form -->
    <form method="post" class="row g-2 align-items-end mb-4">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Hospital Name</label>
            <input class="form-control" name="hospital_name" placeholder="e.g., City Hospital" required>
        </div>

        <div class="col-12 col-md-5">
            <label class="form-label mb-1">Address</label>
            <input class="form-control" name="address" placeholder="Street, City" required>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Phone</label>
            <input class="form-control" name="phone" placeholder="03XXXXXXXXX" required>
        </div>

        <div class="col-12 d-grid mt-2">
            <button class="btn btn-success" name="add" type="submit">
                Add Hospital
            </button>
        </div>
    </form>

    <!-- Hospitals Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th style="width:70px;">#</th>
                    <th>Hospital Name</th>
                    <th>Address</th>
                    <th style="width:160px;">Phone</th>
                    <th style="width:130px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0): ?>
                    <?php $i = 1; while ($h = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($h['hospital_name']) ?></td>
                            <td><?= htmlspecialchars($h['address']) ?></td>
                            <td><?= htmlspecialchars($h['phone']) ?></td>
                            <td>
                                <a
                                    href="addHospital.php?delete=<?= (int)$h['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this hospital?');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-muted">
                            No hospitals added yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../base/footer.php'; ?>
