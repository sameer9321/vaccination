<?php
$pageTitle = "Admin Dashboard";
include '../base/header.php';
include '../includes/db.php';

function countRows($conn,$table,$where=""){
    $sql = "SELECT COUNT(*) as total FROM $table $where";
    $res = mysqli_query($conn,$sql);
    return mysqli_fetch_assoc($res)['total'];
}

$children  = countRows($conn,"children");
$bookings  = countRows($conn,"bookings");
$hospitals = countRows($conn,"hospitals");
$requests  = countRows($conn,"requests","WHERE status='pending'");
$vaccines  = countRows($conn,"vaccines");
?>

<style>
.section-title{
    font-weight:700;
    margin:30px 0 15px;
}
.menu-card{
    border-radius:14px;
    padding:22px;
    text-align:center;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    transition:.25s;
}
.menu-card:hover{
    transform:translateY(-6px);
}
.stat{
    font-size:24px;
    font-weight:700;
}
</style>


<!-- ===== Stats ===== -->
<div class="row mb-4">

<?php
$stats = [
["Children",$children,"primary"],
["Bookings",$bookings,"success"],
["Hospitals",$hospitals,"warning"],
["Pending Requests",$requests,"danger"],
["Vaccines",$vaccines,"info"]
];

foreach($stats as $s){
?>

<div class="col-md-3">
    <div class="menu-card bg-<?php echo $s[2]; ?> text-white">
        <div class="stat"><?php echo $s[1]; ?></div>
        <div><?php echo $s[0]; ?></div>
    </div>
</div>

<?php } ?>
</div>



<!-- ===== Management Section ===== -->
<h5 class="section-title">Management</h5>

<div class="row g-4">

<?php
$cards = [

["All Child Details","children.php","fa-child"],
["Vaccination Dates","vaccination_dates.php","fa-calendar"],
["Vaccination Reports","reports.php","fa-file-text"],
["Vaccine List","vaccines.php","fa-medkit"],
["Parent Requests","requests.php","fa-envelope"],
["Add Hospital","addHospital.php","fa-plus"],
["Hospital List","hospitalslist.php","fa-hospital-o"],
["Booking Details","bookings.php","fa-book"]

];

foreach($cards as $c){
?>

<div class="col-md-3">
    <div class="menu-card">
        <i class="fa <?php echo $c[2]; ?> fa-2x mb-2"></i>
        <h6><?php echo $c[0]; ?></h6>
        <a href="<?php echo $c[1]; ?>" class="btn btn-primary btn-sm">Open</a>
    </div>
</div>

<?php } ?>

</div>

<?php include '../base/footer.php'; ?>
