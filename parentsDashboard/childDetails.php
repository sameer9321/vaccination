<!doctype html>
<html lang="en">
<head>
<title>Child Details</title>
<link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
<div class="container mt-5">
    <h2>Child Details</h2>
    <p>View and update your child's vaccination information below:</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Child Name</th>
                <th>Birth Date</th>
                <th>Age</th>
                <th>Vaccination Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Example row, replace with PHP loop from database -->
            <tr>
                <td>John Doe</td>
                <td>01-01-2023</td>
                <td>3 yrs</td>
                <td>Up to date</td>
                <td><a href="editChild.php?id=1" class="btn btn-sm btn-primary">Edit</a></td>
            </tr>
        </tbody>
    </table>
    <a href="addChild.php" class="btn btn-success">Add New Child</a>
</div>
</body>
</html>
