<?php
$pageTitle = "Admin Dashboard";
include '../base/header.php';
?>
 <div class="block-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index-parent.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ul>
            </div>
<div class="row clearfix">

    <!-- Child Details -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>All Child Details</h5>
                <a href="children.php" class="btn btn-primary">View</a>
            </div>
        </div>
    </div>

    <!-- Vaccination Dates -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Upcoming Vaccination Dates</h5>
                <a href="vaccination_dates.php" class="btn btn-info">View</a>
            </div>
        </div>
    </div>

    <!-- Reports -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Vaccination Reports</h5>
                <a href="reports.php" class="btn btn-success">View</a>
            </div>
        </div>
    </div>

    <!-- Vaccine List -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Vaccine Availability</h5>
                <a href="vaccines.php" class="btn btn-warning">View</a>
            </div>
        </div>
    </div>

    <!-- Parent Requests -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Parent Requests</h5>
                <a href="requests.php" class="btn btn-danger">Approve / Reject</a>
            </div>
        </div>
    </div>

    <!-- Hospitals -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Manage Hospitals</h5>
                <a href="hospitals.php" class="btn btn-secondary">Manage</a>
            </div>
        </div>
    </div>

    <!-- Bookings -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="body text-center">
                <h5>Booking Details</h5>
                <a href="bookings.php" class="btn btn-dark">View</a>
            </div>
        </div>
    </div>

</div>

<?php include '../base/footer.php'; ?>
