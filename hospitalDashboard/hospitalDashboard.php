<?php include '../base/header.php'; ?>
    <!-- Main Content -->
        <div class="container-fluid">

            <!-- Dashboard Cards -->
            <div class="row clearfix">
                <div class="col-md-4">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-pencil-square-o fa-2x mb-2"></i>
                            <h5>Update Vaccine Status</h5>
                            <p>Mark vaccination as completed or pending</p>
                            <a href="updateVaccineStatus.php" class="btn btn-primary btn-sm">Update Status</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-calendar fa-2x mb-2"></i>
                            <h5>Appointments</h5>
                            <p>View all upcoming appointments for your hospital</p>
                            <a href="appointments.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card info-box">
                        <div class="body text-center">
                            <i class="fa fa-file-text fa-2x mb-2"></i>
                            <h5>Reports</h5>
                            <p>Check vaccination report status</p>
                            <a href="reports.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div class="row clearfix">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="header">
                            <h2>Upcoming Appointments</h2>
                        </div>
                        <div class="body">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Child Name</th>
                                        <th>Age</th>
                                        <th>Vaccine</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['child_name']) ?></td>
                                            <td><?= htmlspecialchars($row['age']) ?></td>
                                            <td><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                            <td>
                                                <a href="updateVaccineStatus.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Update</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">No appointments found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../base/footer.php'; ?>

