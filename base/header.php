<?php
// ===============================
// base/header.php (UPDATED UI)
// ===============================
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

/* Fetch Profile Picture */
$picQuery = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = '$userId'");
$picRow   = mysqli_fetch_assoc($picQuery);
$userPic  = (!empty($picRow['profile_pic'])) ? $picRow['profile_pic'] : 'user.png';

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

    <!-- Existing CSS (keep) -->
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!--
      Tailwind setup notes:
      - We keep Bootstrap, and use Tailwind for new UI pieces.
      - preflight disabled to avoid breaking existing Bootstrap styling.
    -->
    <script>
      tailwind.config = {
        corePlugins: { preflight: false },
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            }
          }
        }
      }
    </script>

    <style>
      body { font-family: Inter, ui-sans-serif, system-ui; background: #f8fafc; }

      /* Fade up helper (used across dashboards) */
      @keyframes twFadeUpKeyframes {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }
      .twFadeUp { animation: twFadeUpKeyframes 520ms ease-out both; }

      /* Make legacy sidebar look cleaner without changing structure */
      #left-sidebar.sidebar {
        background: #0b1220;
      }
      #left-sidebar .user-account {
        padding: 18px 14px;
      }
      #left-sidebar .user-account p,
      #left-sidebar .user-account strong {
        color: #e5e7eb !important;
      }
      #left-sidebar .sidebar-nav .metismenu > li.header {
        color: rgba(229,231,235,0.7);
      }
      #left-sidebar .sidebar-nav .metismenu a {
        border-radius: 12px;
        margin: 6px 10px;
        padding: 10px 12px;
        color: rgba(229,231,235,0.9);
        transition: all 180ms ease;
      }
      #left-sidebar .sidebar-nav .metismenu a:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
        transform: translateX(2px);
      }

      /* Top navbar polish */
      .navbar.navbar-fixed-top {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(15,23,42,0.08);
      }
      .navbar .navbar-brand a {
        font-weight: 800;
        letter-spacing: 0.5px;
      }
      .navbar .icon-menu i {
        transition: transform 180ms ease, color 180ms ease;
      }
      .navbar .icon-menu:hover i {
        transform: scale(1.08);
      }

      /* Main content spacing polish */
      #main-content {
        background: transparent;
      }
      #main-content .block-header h2 {
        font-weight: 800;
        letter-spacing: -0.2px;
        color: #0f172a;
      }

      /* Smooth button feel everywhere (safe, light touch) */
      .btn, button, a.btn {
        transition: transform 150ms ease, box-shadow 150ms ease, background-color 150ms ease, color 150ms ease, border-color 150ms ease;
      }
      .btn:active, button:active, a.btn:active {
        transform: scale(0.99);
      }
    </style>
</head>

<body data-theme="light" class="font-nunito">
<div id="wrapper" class="theme-cyan">

    <nav class="navbar navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-brand">
                <button type="button" class="btn-toggle-offcanvas">
                    <i class="fa fa-bars"></i>
                </button>
                <a href="<?= $dash_link ?>"> <?= strtoupper($role) ?> PANEL</a>
            </div>
            <div class="navbar-right">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="../logout.php" class="icon-menu" title="Logout">
                            <i class="fa fa-power-off"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="left-sidebar" class="sidebar">
        <div class="sidebar-scroll">
            <div class="user-account text-center">
                <div class="mx-auto w-fit twFadeUp">
                    <img src="../assets/images/<?= $userPic ?>"
                         class="rounded-circle user-photo ring-2 ring-white/10"
                         width="72"
                         style="height:72px; object-fit: cover;">
                </div>

                <p class="mt-2 mb-0 text-sm opacity-80">Welcome,</p>
                <strong class="text-sm"><?= $username ?></strong>

                <div class="mx-auto mt-3 h-px w-10/12 bg-white/10"></div>
            </div>

            <nav id="left-sidebar-nav" class="sidebar-nav">
                <ul class="metismenu">
                    <li class="header">Main Navigation</li>
                    <li><a href="<?= $dash_link ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>

                    <?php if($role == 'admin'): ?>
                        <li><a href="hospitalslist.php"><i class="fa fa-hospital-o"></i> Hospitals</a></li>
                        <li><a href="vaccines.php"><i class="fa fa-flask"></i> Vaccines</a></li>
                        <li><a href="reports.php"><i class="fa fa-file-text"></i> Global Reports</a></li>

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
