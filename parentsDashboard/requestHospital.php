<!doctype html>
<html lang="en">
<head>
<title>Request Hospital</title>
<link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
<div class="container mt-5">
    <h2>Request Hospital</h2>
    <p>Request your preferred hospital for vaccination schedules:</p>

    <form method="POST" action="requestHospitalProcess.php">
        <div class="form-group">
            <label>Child Name</label>
            <select class="form-control" name="child_name">
                <option>John Doe</option>
                <option>Jane Doe</option>
            </select>
        </div>
        <div class="form-group">
            <label>Hospital Request</label>
            <input type="text" class="form-control" name="hospital_request" placeholder="Enter hospital name">
        </div>
        <button type="submit" class="btn btn-primary">Submit Request</button>
    </form>

    <hr>
    <h4>Previous Requests</h4>
    <ul class="list-group">
        <li class="list-group-item">Requested City Hospital for John Doe – Pending</li>
    </ul>
</div>
</body>
</html>
