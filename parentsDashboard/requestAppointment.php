<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'parent') {
    header("Location: ../../login.php");
    exit();
}

$parentId = $_SESSION['user_id'];
$hospitals = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childId = $_POST['child_id'];
    $vaccineName = $_POST['vaccine_name'];
    $hospitalId = $_POST['hospital_id'];
    $appointmentDate = $_POST['appointment_date'];

    $sql = "INSERT INTO requests (parent_id, child_id, vaccine_name, hospital_id, appointment_date, status)
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $parentId, $childId, $vaccineName, $hospitalId, $appointmentDate);
    $stmt->execute();
    $stmt->close();

    header("Location: parentdashboard.php?request_success=1");
    exit();
}

// Fetch available hospitals for the parent
$sql = "SELECT * FROM hospitals";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $hospitals[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Appointment</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h3>Request a Vaccine Appointment</h3>
    <form method="post">
        <div class="form-group">
            <label for="child_id">Child</label>
            <select name="child_id" class="form-control" required>
                <!-- Populate with child's details -->
                <option value="1">Child 1</option>
                <option value="2">Child 2</option>
            </select>
        </div>
        <div class="form-group">
            <label for="vaccine_name">Vaccine Name</label>
            <input type="text" name="vaccine_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="hospital_id">Select Hospital</label>
            <select name="hospital_id" class="form-control" required>
                <?php foreach ($hospitals as $hospital): ?>
                    <option value="<?= $hospital['id'] ?>"><?= $hospital['hospital_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="appointment_date">Appointment Date</label>
            <input type="date" name="appointment_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit Request</button>
    </form>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
