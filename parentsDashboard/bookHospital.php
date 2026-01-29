<!doctype html>
<html lang="en">
<head>
<title>Book Hospital</title>
<link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
<div class="container mt-5">
    <h2>Book Hospital for Vaccination</h2>
    <form method="POST" action="bookHospitalProcess.php">
        <div class="form-group">
            <label>Child Name</label>
            <select class="form-control" name="child_name">
                <option>John Doe</option>
                <option>Jane Doe</option>
            </select>
        </div>
        <div class="form-group">
            <label>Hospital Name</label>
            <input type="text" class="form-control" name="hospital_name" placeholder="Enter hospital name">
        </div>
        <div class="form-group">
            <label>Vaccination Date</label>
            <input type="date" class="form-control" name="vaccine_date">
        </div>
        <button type="submit" class="btn btn-primary">Book</button>
    </form>

    <hr>
    <h4>Booked Hospitals</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Child</th>
                <th>Hospital</th>
                <th>Vaccination Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John Doe</td>
                <td>City Hospital</td>
                <td>05-02-2026</td>
                <td>Confirmed</td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
