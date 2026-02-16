<?php
// ===============================
// mainadmin/index.php (UPDATED UI)
// ===============================
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

<!--
  UI notes:
  - Cards hover: transition, hover lift, shadow
  - Stats animate in: fade up keyframe (defined in header.php)
  - Responsive: grid switches 1 -> 2 -> 3 -> 5 cols
-->

<div class="py-4">
    <!-- Top stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <?php
        $stats = [
            ["Children", $children, "from-indigo-600 to-indigo-500", "fa-users"],
            ["Bookings", $bookings, "from-emerald-600 to-emerald-500", "fa-check-circle"],
            ["Hospitals", $hospitals, "from-amber-600 to-amber-500", "fa-hospital-o"],
            ["Pending Requests", $requests, "from-rose-600 to-rose-500", "fa-clock-o"],
            ["Vaccine Types", $vaccines, "from-sky-600 to-sky-500", "fa-medkit"]
        ];

        foreach($stats as $idx => $s) {
        ?>
        <div class="twFadeUp" style="animation-delay: <?= (int)$idx * 70 ?>ms;">
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="absolute inset-0 bg-gradient-to-br <?= $s[2] ?> opacity-[0.10]"></div>

                <div class="relative p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/70 text-slate-900 ring-1 ring-white/30">
                            <i class="fa <?= $s[3] ?> text-lg"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-extrabold tracking-tight text-slate-900"><?= $s[1] ?></div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-600"><?= $s[0] ?></div>
                        </div>
                    </div>

                    <div class="mt-4 h-px w-full bg-slate-100"></div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-600">
                        <span class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-slate-400 transition group-hover:bg-slate-900"></span>
                            Live count
                        </span>
                        <span class="rounded-full bg-white px-2 py-1 ring-1 ring-slate-200">Updated</span>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Section title -->
    <div class="mt-8 twFadeUp">
        <div class="flex items-center justify-between">
            <div>
                <h5 class="text-lg font-semibold text-slate-900">
                    <i class="fa fa-gears mr-2 text-slate-600"></i>System Management
                </h5>
                <p class="mt-1 text-sm text-slate-600">Manage hospitals, vaccines, bookings, and reports.</p>
            </div>
        </div>
    </div>

    <!-- Management cards -->
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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

        foreach($cards as $idx => $c) {
        ?>
        <div class="twFadeUp" style="animation-delay: <?= 120 + (int)$idx * 60 ?>ms;">
            <div class="group h-full rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-slate-900 group-hover:text-white">
                        <i class="fa <?= $c[2] ?> text-xl"></i>
                    </div>

                    <div class="min-w-0">
                        <h6 class="truncate text-sm font-semibold text-slate-900"><?= $c[0] ?></h6>
                        <p class="mt-1 text-sm text-slate-600"><?= $c[3] ?></p>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Manage module</span>

                    <a href="<?= $c[1] ?>"
                       class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]">
                        Manage
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <?php if($requests > 0): ?>
    <div class="mt-8 twFadeUp">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-rose-200">
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                        <i class="fa fa-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Action Required</div>
                        <div class="mt-1 text-sm text-slate-600">
                            You have <span class="font-semibold text-rose-700"><?= $requests ?></span> parent requests awaiting approval.
                        </div>
                    </div>
                </div>

                <a href="requests.php"
                   class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 active:scale-[0.99]">
                    Review Requests
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include "../base/footer.php"; ?>
