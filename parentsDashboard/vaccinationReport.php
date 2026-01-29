<?php include '../base/header.php'; ?>
<div class="container mt-5">
    <h2>Vaccination Report</h2>
    <p>View your child's previous vaccination reports:</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Child Name</th>
                <th>Vaccine</th>
                <th>Date Taken</th>
                <th>Status</th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John Doe</td>
                <td>Polio</td>
                <td>05-01-2026</td>
                <td>Completed</td>
                <td><a href="#" class="btn btn-sm btn-info">Download</a></td>
            </tr>
            <tr>
                <td>Jane Doe</td>
                <td>MMR</td>
                <td>10-01-2026</td>
                <td>Completed</td>
                <td><a href="#" class="btn btn-sm btn-info">Download</a></td>
            </tr>
        </tbody>
    </table>
</div>
<?php include '../base/footer.php'; ?>
