<?php
session_start();

$pageTitle = "Add Child";
include '../includes/db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'parent') {
    header("Location: ../../../index.php");
    exit;
}

$parentUserId = (int)($_SESSION['user_id'] ?? 0);
$error = "";
$success = "";

if (isset($_POST['add_child'])) {
    $child_name = trim($_POST['child_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $vaccination_status = trim($_POST['vaccination_status'] ?? 'Pending');

    if ($child_name === '' || $birth_date === '') {
        $error = "Please fill all required fields.";
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO children (parent_id, child_name, birth_date, vaccination_status)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt) {
            $error = "Server error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "isss", $parentUserId, $child_name, $birth_date, $vaccination_status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: childDetails.php?added=1");
            exit;
        }
    }
}

include '../base/header.php';
?>

<div class="container-fluid">
    <div class="block-header">
        <ul class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="parentdashboard.php"><i class="fa fa-dashboard"></i></a>
            </li>
            <li class="breadcrumb-item">
                <a href="childDetails.php">Child Details</a>
            </li>
            <li class="breadcrumb-item active">Add Child</li>
        </ul>
    </div>

    <div class="card" style="border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,0.08);">
        <div class="body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="m-0">Add Child</h4>
                <a href="childDetails.php" class="btn btn-outline-primary btn-sm">Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label mb-1">Child Name</label>
                    <input type="text" name="child_name" class="form-control" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">Birth Date</label>
                    <input type="date" name="birth_date" class="form-control" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Vaccination Status</label>
                    <select name="vaccination_status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Up to date">Up to date</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="col-12 d-grid">
                    <button type="submit" name="add_child" class="btn btn-success">
                        Save Child
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../base/footer.php'; ?>
