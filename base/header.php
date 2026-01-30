<?php
session_start();

/* =========================
   AUTH CHECK
========================= */
if(!isset($_SESSION['role'])){
    header("Location: ../login.php");
    exit;
}

$role     = strtolower($_SESSION['role']);
$username = htmlspecialchars($_SESSION['username']);


/* =========================
   DYNAMIC PAGE TITLE (optional)
   You can set $pageTitle in any page
========================= */
$pageTitle = $pageTitle ?? ucfirst($role) . " Dashboard";
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>:: Iconic <?= $pageTitle ?></title>

<link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">

<!-- Bootstrap -->
<link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">

<!-- FontAwesome -->
<link rel="stylesheet" href="../assets/vendor/font-awesome/css/font-awesome.min.css">

<!-- Main CSS -->
<link rel="stylesheet" href="../assets/css/main.css">
</head>


<body data-theme="light" class="font-nunito">
<div id="wrapper" class="theme-cyan">

<!-- =========================
     TOP NAVBAR
========================= -->
<nav class="navbar navbar-fixed-top">
    <div class="container-fluid">

        <div class="navbar-brand">
            <button type="button" class="btn-toggle-offcanvas">
                <i class="fa fa-bars"></i>
            </button>

            <a href="#">
                <?= strtoupper($role) ?>
            </a>
        </div>

        <div class="navbar-right">
            <ul class="nav navbar-nav">
                <li>
                    <a href="../logout.php" class="icon-menu">
                        <i class="fa fa-power-off"></i>
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>



<!-- =========================
     SIDEBAR
========================= -->
<div id="left-sidebar" class="sidebar">
<div class="sidebar-scroll">

    <div class="user-account text-center">

        <img src="../assets/images/user.png"
             class="rounded-circle user-photo"
             width="70">

        <p class="mt-2 mb-0">Welcome,</p>
        <strong><?= $username ?></strong>

        <hr>
    </div>


    <nav id="left-sidebar-nav" class="sidebar-nav">
        <ul class="metismenu">

        <?php if($role == 'admin'): ?>

            <li><a href="admin_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fa fa-users"></i> All Users</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>

        <?php elseif($role == 'hospital'): ?>

            <li><a href="hospitalDashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="updateVaccineStatus.php"><i class="fa fa-pencil-square-o"></i> Update Vaccine Status</a></li>
            <li><a href="appointments.php"><i class="fa fa-calendar"></i> Appointments</a></li>

        <?php elseif($role == 'parent'): ?>

            <li><a href="index-parent.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="childDetails.php"><i class="fa fa-user"></i> Child Details</a></li>
            <li><a href="vaccinationDates.php"><i class="fa fa-calendar"></i> Vaccination Dates</a></li>
            <li><a href="bookHospital.php"><i class="fa fa-hospital-o"></i> Book Hospital</a></li>
            <li><a href="requestHospital.php"><i class="fa fa-envelope"></i> Request Hospital</a></li>
            <li><a href="vaccinationReport.php"><i class="fa fa-file-text"></i> Vaccination Report</a></li>

        <?php endif; ?>

        </ul>
    </nav>

</div>
</div>



<!-- =========================
     MAIN CONTENT START
========================= -->
<div id="main-content">
<div class="container-fluid">

<div class="block-header">
    <h2><?= $pageTitle ?></h2>
</div>
