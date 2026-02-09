<?php
$pageTitle = "Admin Dashboard";
include '../base/header.php';
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

function countRows($conn, $table, $where="") {
    $table = mysqli_real_escape_string($conn, $table);
    $sql = "SELECT COUNT(*) as total FROM `$table` $where";
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    return mysqli_fetch_assoc($res)['total'];
}

$children  = countRows($conn, "children");
$bookings  = countRows($conn, "bookings");
$hospitals = countRows($conn, "hospitals");
$requests  = countRows($conn, "hospital_requests", "WHERE status='Pending'");
$vaccines  = countRows($conn, "vaccines");

?>

<style>
    .section-title { font-weight: 700; margin: 30px 0 15px; color: #333; }
    .menu-card { 
        border-radius: 14px; 
        padding: 22px; 
        text-align: center; 
        box-shadow: 0 6px 18px rgba(0,0,0,0.08); 
        transition: .25s; 
        background: #fff;
        height: 100%;
    }
    .menu-card:hover { transform: translateY(-6px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
    .stat { font-size: 28px; font-weight: 700; display: block; }
    .stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
    .icon-box { color: #007bff; margin-bottom: 15px; }
</style>

<div class="container-fluid py-4">
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ["Children", $children, "primary", "fa-users"],
            ["Bookings", $bookings, "success", "fa-check-circle"],
            ["Hospitals", $hospitals, "warning", "fa-hospital-o"],
            ["Pending Requests", $requests, "danger", "fa-clock-o"],
            ["Vaccine Types", $vaccines, "info", "fa-medkit"]
        ];

        foreach($stats as $s) {
        ?>
        <div class="col">
            <div class="menu-card bg-<?php echo $s[2]; ?> text-white">
                <i class="fa <?php echo $s[3]; ?> mb-2"></i>
                <span class="stat"><?php echo $s[1]; ?></span>
                <span class="stat-label"><?php echo $s[0]; ?></span>
            </div>
        </div>
        <?php } ?>
    </div>

    <h5 class="section-title"><i class="fa fa-gears me-2"></i>System Management</h5>
    
    <div class="row g-4">
        <?php
        $cards = [
            ["All Child Details", "children.php", "fa-child", "View all registered infants"],
            ["Vaccination Dates", "vaccination_dates.php", "fa-calendar", "Upcoming schedules"],
            ["Vaccination Reports", "reports.php", "fa-file-text-o", "Date-wise reports"],
            ["Vaccine Inventory", "vaccines.php", "fa-flask", "Manage stocks"],
            ["Parent Requests", "requests.php", "fa-envelope-o", "Approve/Reject requests"],
            ["Add New Hospital", "addHospital.php", "fa-plus-circle", "Expand network"],
            ["Hospital List", "hospitalslist.php", "fa-list-ul", "Update/Delete"],
            ["Booking Details", "bookings.php", "fa-book", "Full booking logs"]
        ];

        foreach($cards as $c) {
        ?>
        <div class="col-md-3">
            <div class="menu-card d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-box"><i class="fa <?php echo $c[2]; ?> fa-3x"></i></div>
                    <h6><?php echo $c[0]; ?></h6>
                    <p class="small text-muted"><?php echo $c[3]; ?></p>
                </div>
                <a href="<?php echo $c[1]; ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">Manage</a>
            </div>
        </div>
        <?php } ?>
    </div>

    <?php if($requests > 0): ?>
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="alert alert-important alert-danger d-flex align-items-center" role="alert">
                <i class="fa fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <strong>Action Required:</strong> You have <?php echo $requests; ?> parent requests awaiting approval. 
                    <a href="requests.php" class="alert-link">Click here to process them.</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include "../base/footer.php"; ?>
