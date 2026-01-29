<?php
$pageTitle = "Parent Dashboard";
include '../base/header.php';
?>
        <div class="container-fluid">
            <div class="block-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index-parent.php"><i class="fa fa-dashboard"></i></a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ul>
            </div>

            <!-- Dashboard Cards -->
            <div class="row clearfix">
                <div class="col-md-3">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-user fa-2x mb-2"></i>
                            <h5>Child Details</h5>
                            <p>Update & maintain vaccination details of your child</p>
                            <a href="childDetails.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-calendar fa-2x mb-2"></i>
                            <h5>Vaccination Dates</h5>
                            <p>Get notified about upcoming vaccination schedules</p>
                            <a href="vaccinationDates.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-hospital-o fa-2x mb-2"></i>
                            <h5>Book Hospital</h5>
                            <p>Search & book hospitals for vaccination dates</p>
                            <a href="bookHospital.php" class="btn btn-primary btn-sm">Book</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-file-text fa-2x mb-2"></i>
                            <h5>Vaccination Report</h5>
                            <p>Check report status of previous vaccinations</p>
                            <a href="vaccinationReport.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Optional: Notifications Section -->
            <div class="row clearfix">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="header">
                            <h2>Notifications</h2>
                        </div>
                        <div class="body">
                            <ul class="list-group">
                                <li class="list-group-item">Upcoming vaccination for <strong>Child Name</strong> on 5th Feb 2026</li>
                                <li class="list-group-item">Your vaccination report for <strong>Child Name</strong> is ready.</li>
                                <li class="list-group-item">Hospital booking confirmed for <strong>Child Name</strong> on 10th Feb 2026</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

<?php include '../base/footer.php'; ?>

