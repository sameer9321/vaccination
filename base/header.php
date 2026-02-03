<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0");
include "../includes/db.php";

/* Auth check */
if (!isset($_SESSION["role"]) || !isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$role     = strtolower($_SESSION['role']);
$username = htmlspecialchars($_SESSION['username']);

// --- NEW LOGIC: Fetch Profile Picture ---
$picQuery = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = '$userId'");
$picRow   = mysqli_fetch_assoc($picQuery);
$userPic  = (!empty($picRow['profile_pic'])) ? $picRow['profile_pic'] : 'user.png';
// ----------------------------------------

$dash_link = "dashboard.php";
if($role == 'admin') $dash_link = "index.php";
if($role == 'hospital') $dash_link = "hospitalDashboard.php";
if($role == 'parent') $dash_link = "parentdashboard.php";

$pageTitle = $pageTitle ?? ucfirst($role) . " Dashboard";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>:: Iconic <?= $pageTitle ?></title>
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>

<body data-theme="light" class="font-nunito">
<div id="wrapper" class="theme-cyan">

    <nav class="navbar navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-brand">
                <button type="button" class="btn-toggle-offcanvas"><i class="fa fa-bars"></i></button>
                <a href="<?= $dash_link ?>"> <?= strtoupper($role) ?> PANEL</a>
            </div>
            <div class="navbar-right">
                <ul class="nav navbar-nav">
                    <li><a href="../logout.php" class="icon-menu"><i class="fa fa-power-off"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="left-sidebar" class="sidebar">
        <div class="sidebar-scroll">
            <div class="user-account text-center">
                <img src="../assets/images/<?= $userPic ?>" class="rounded-circle user-photo" width="70" style="height:70px; object-fit: cover;">
                <p class="mt-2 mb-0">Welcome,</p>
                <strong><?= $username ?></strong>
                <hr>
            </div>

            <nav id="left-sidebar-nav" class="sidebar-nav">
                <ul class="metismenu">
                    <li class="header">Main Navigation</li>
                    <li><a href="<?= $dash_link ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>

                    <?php if($role == 'admin'): ?>
                        <li><a href="hospitalslist.php"><i class="fa fa-hospital-o"></i> Hospitals</a></li>
                        <li><a href="vaccines.php"><i class="fa fa-flask"></i> Vaccines</a></li>
                        <li><a href="users.php"><i class="fa fa-users"></i> All Users</a></li>
                        <li><a href="reports.php"><i class="fa fa-file-text"></i> Global Reports</a></li>
                        <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>

                    <?php elseif($role == 'hospital'): ?>
                        <li><a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a></li>
                        <li><a href="manageRequests.php"><i class="fa fa-envelope"></i> Association Requests</a></li>
                        <li><a href="reportes.php"><i class="fa fa-file-text"></i> Vaccination Logs</a></li>

                    <?php elseif($role == 'parent'): ?>
                        <li><a href="childDetails.php"><i class="fa fa-child"></i> Child Details</a></li>
                        <li><a href="requestHospitals.php"><i class="fa fa-send"></i> Request Association</a></li>
                        <li><a href="bookHospital.php"><i class="fa fa-calendar-plus-o"></i> Book Vaccination</a></li>
                        <li><a href="vaccinationReport.php"><i class="fa fa-file-pdf-o"></i> My Reports</a></li>
                        <li><a href="profile.php"><i class="fa fa-user-circle"></i> My Profile</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>

    <div id="main-content">
        <div class="container-fluid">
            <div class="block-header" style="padding-top: 20px;">
                <h2><?= $pageTitle ?></h2>
            </div>