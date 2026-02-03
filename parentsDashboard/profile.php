<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "My Profile";
include "../includes/db.php";

/* Parent Auth Check */
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "parent") {
    header("Location: ../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$parentId = (int)($_SESSION["parent_id"] ?? 0);

/* Resolve parent_id if not in session */
if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    
    if ($rowU) {
        $userEmail = $rowU["email"];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id, parent_name FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $userEmail);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = (int)$rowP["parent_id"];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent profile not linked. Please re-login.");
}

/* Update Profile Logic */
if (isset($_POST["save_profile"])) {
    $parentName = trim((string)$_POST["parent_name"]);
    $address = trim((string)$_POST["address"]);
    $phone = trim((string)$_POST["phone"]);

    if ($parentName !== "" && $address !== "" && $phone !== "") {
        $stmtUp = mysqli_prepare($conn, "UPDATE parents SET parent_name = ?, address = ?, phone = ? WHERE parent_id = ?");
        mysqli_stmt_bind_param($stmtUp, "sssi", $parentName, $address, $phone, $parentId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        
        header("Location: profile.php?saved=1");
        exit;
    } else {
        header("Location: profile.php?error=1");
        exit;
    }
}

/* Fetch Current Parent Data */
$stmt = mysqli_prepare($conn, "SELECT parent_name, address, phone, email FROM parents WHERE parent_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$parent = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<div class="container-fluid">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="parentdashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile Settings</li>
                </ul>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 text-right">
                <span class="badge badge-primary">Parent ID: #<?= $parentId ?></span>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Profile</strong> Picture</h2>
                </div>
                <div class="body text-center">
                    <?php 
                        // Fetch pic from users table as established in previous steps
                        $picQ = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = '$userId'");
                        $picR = mysqli_fetch_assoc($picQ);
                        $userPic = (!empty($picR['profile_pic'])) ? $picR['profile_pic'] : 'user.png';
                    ?>
                    <div style="width: 150px; height: 150px; margin: 0 auto; overflow: hidden;" class="rounded-circle shadow mb-3">
                        <img src="../assets/images/<?= $userPic ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Profile">
                    </div>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="profile_image" class="form-control" required>
                        </div>
                        <button type="submit" name="upload_pic" class="btn btn-info btn-round btn-block">Change Photo</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="header">
                    <h2><strong>Personal</strong> Details <small>Update your contact information</small></h2>
                </div>
                <div class="body">
                    <?php if (isset($_GET["saved"])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Profile updated successfully.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Full Name</label>
                                    <input type="text" name="parent_name" class="form-control" 
                                           value="<?= htmlspecialchars($parent["parent_name"] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Email Address (Read Only)</label>
                                    <input type="email" class="form-control" 
                                           value="<?= htmlspecialchars($parent["email"] ?? '') ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label>Residential Address</label>
                                    <input type="text" name="address" class="form-control" 
                                           value="<?= htmlspecialchars($parent["address"] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Contact Number</label>
                                    <input type="text" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($parent["phone"] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <button type="submit" name="save_profile" class="btn btn-primary btn-round">
                                    <i class="fa fa-save mr-1"></i> Save Changes
                                </button>
                                <a href="parentdashboard.php" class="btn btn-default btn-round btn-simple">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>