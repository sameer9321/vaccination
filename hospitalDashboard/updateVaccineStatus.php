<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital'){
    header("Location: ../../login.php");
    exit();
}

require '../../includes/db.php';

$hospital_id = $_SESSION['user_id'];

// Update status if form submitted
if(isset($_POST['status_update'])){
    $appt_id = intval($_POST['appt_id']);
    $status = $_POST['status'] === 'Vaccinated' ? 'Vaccinated' : 'Pending';
    $conn->query("UPDATE appointments SET status='$status' WHERE id=$appt_id AND hospital_id=$hospital_id");
    header("Location: updateVaccineStatus.php");
    exit();
}

// Fetch appointments for this hospital
$sql = "SELECT a.id, c.name as child_name, c.age, a.vaccine_name, a.appointment_date, a.status 
        FROM appointments a 
        JOIN children c ON a.child_id = c.id 
        WHERE a.hospital_id = $hospital_id";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>:: Iconic Hospital :: Update Vaccine Status</title>
<link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body class="font-nunito bg-light">
<div class="container-fluid mt-4">

<h2 class="mb-4">Update Vaccine Status</h2>

<table class="table table-bordered table-striped">
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
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="appt_id" value="<?= $row['id'] ?>">
                        <select name="status" class="form-control form-control-sm">
                            <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                            <option value="Vaccinated" <?= $row['status']=='Vaccinated'?'selected':'' ?>>Vaccinated</option>
                        </select>
                        <button type="submit" name="status_update" class="btn btn-sm btn-success">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center">No appointments found</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</div>
<script src="../../assets/bundles/libscripts.bundle.js"></script>    
<script src="../../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
