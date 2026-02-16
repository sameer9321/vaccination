<?php
session_start();
$pageTitle = "Parent Dashboard";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int) ($_SESSION["user_id"] ?? 0);
$username = (string) ($_SESSION["username"] ?? "Parent");
$parentId = (int) ($_SESSION["parent_id"] ?? 0);

// Auto-create/link parent profile if missing
if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $userRow = mysqli_fetch_assoc($resU);
    if ($userRow) {
        $email = $userRow['email'];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $email);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $pData = mysqli_fetch_assoc($resP);
        if ($pData) {
            $parentId = $pData['parent_id'];
        } else {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, '')");
            mysqli_stmt_bind_param($stmtIns, "ss", $username, $email);
            mysqli_stmt_execute($stmtIns);
            $parentId = mysqli_insert_id($conn);
        }
        $_SESSION["parent_id"] = $parentId;
    }
}

// Statistics Queries
$totalChildren = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM children WHERE parent_id = $parentId"))['total'];
$totalUpcoming = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE()"))['total'];
$totalPending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospital_requests WHERE parent_id = $parentId AND status = 'Pending'"))['total'];
$totalCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.status IN ('Vaccinated', 'Completed')"))['total'];
$totalHospitals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospitals"))['total'];

$upcomingList = mysqli_query($conn, "SELECT c.child_name, b.vaccine_name, b.booking_date, b.status FROM bookings b JOIN children c ON b.child_id = c.child_id WHERE c.parent_id = $parentId AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC LIMIT 4");

include "../base/header.php";
?>

<!--
  Update:
  - Added a new service card: "Add Child" -> addChild.php
  - Styling matches the existing Tailwind card grid and hover transitions
-->

<div class="py-4">
    <!-- Welcome strip -->
    <div class="twFadeUp mb-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                    Parent portal
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    Welcome, <?= htmlspecialchars($username) ?>
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Track your children, vaccination schedules, and reports in one place.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="bookHospital.php"
                   class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]">
                    <i class="fa fa-calendar-plus-o mr-2"></i>
                    Book vaccination
                </a>
                <a href="profile.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                    <i class="fa fa-user-circle mr-2"></i>
                    Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <?php
        $stats = [
            ["Total Children", $totalChildren, "from-violet-600 to-violet-500", "fa-child"],
            ["Upcoming", $totalUpcoming, "from-emerald-600 to-emerald-500", "fa-calendar-check-o"],
            ["Pending Requests", $totalPending, "from-amber-600 to-amber-500", "fa-clock-o"],
            ["Vaccinated", $totalCompleted, "from-sky-600 to-sky-500", "fa-file-text"],
            ["Hospitals", $totalHospitals, "from-slate-800 to-slate-700", "fa-hospital-o"]
        ];

        foreach ($stats as $idx => $s) {
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

    <!-- Services -->
    <div class="mt-8 twFadeUp">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h5 class="text-lg font-semibold text-slate-900">
                    <i class="fa fa-th-large mr-2 text-slate-600"></i>Parent Services
                </h5>
                <p class="mt-1 text-sm text-slate-600">Quick access to your main actions.</p>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <?php
        // Added: Add Child card (links to addChild.php)
        $cards = [
            ["Add Child", "addChild.php", "fa-plus-circle", "Register a new child profile"],
            ["Child Details", "childDetails.php", "fa-users", "Manage your registered children"],
            ["Vaccination Dates", "vaccinationDates.php", "fa-calendar", "View upcoming vaccine schedules"],
            ["Hospital Requests", "requestHospitals.php", "fa-send", "Track requests sent to hospitals"],
            ["Vaccination History", "vaccinationReport.php", "fa-history", "Download vaccination reports"],
            ["Book Hospital", "bookHospital.php", "fa-plus-square", "Find and book nearby hospitals"],
            ["Profile Settings", "profile.php", "fa-user-circle", "Update your contact information"]
        ];

        foreach ($cards as $idx => $c) {
        ?>
            <div class="twFadeUp" style="animation-delay: <?= 120 + (int)$idx * 60 ?>ms;">
                <div class="group h-full rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-violet-600 group-hover:text-white group-hover:ring-violet-600">
                            <i class="fa <?= $c[2] ?> text-xl"></i>
                        </div>

                        <div class="min-w-0">
                            <h6 class="truncate text-sm font-semibold text-slate-900"><?= $c[0] ?></h6>
                            <p class="mt-1 text-sm text-slate-600"><?= $c[3] ?></p>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Open module</span>

                        <a href="<?= $c[1] ?>"
                           class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm ring-1 ring-violet-200 transition hover:bg-violet-50 active:scale-[0.99]">
                            Access
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Upcoming schedules + search -->
    <div class="mt-8 twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h6 class="text-sm font-semibold text-slate-900">
                    <i class="fa fa-bell-o mr-2 text-violet-700"></i>Upcoming Vaccination Schedules
                </h6>
                <p class="mt-1 text-sm text-slate-600">Search and review the next vaccinations.</p>
            </div>

            <!-- Search Bar -->
            <div class="w-full sm:w-80">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        id="childSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        placeholder="Search child name..."
                    >
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Child Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Vaccine Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Scheduled Date</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php
                    $i = 1;
                    if (mysqli_num_rows($upcomingList) > 0):
                        while ($u = mysqli_fetch_assoc($upcomingList)):
                            $st = strtolower((string)$u['status']);
                            $isPending = ($st === 'pending');
                    ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($u['child_name']) ?></div>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-100">
                                    <?= htmlspecialchars($u['vaccine_name']) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                                <i class="fa fa-calendar-o mr-1 text-slate-400"></i>
                                <?= date('d M, Y', strtotime($u['booking_date'])) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <?php if ($isPending): ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                        <?= htmlspecialchars($u['status']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <?= htmlspecialchars($u['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No upcoming vaccinations found.</div>
                                    <div class="text-slate-600">When bookings are added, they will show up here.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            Tip: On small screens, scroll sideways to view all columns.
        </div>
    </div>

    <script>
        // Search filter (kept logic same, only targets the upcoming table rows)
        document.getElementById('childSearch').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            let table = document.querySelector('table tbody');
            let rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                let childNameCell = rows[i].getElementsByTagName('td')[1]; // 2nd column (Child Name)
                if (childNameCell) {
                    let txtValue = childNameCell.textContent || childNameCell.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        });
    </script>
</div>

<?php include "../base/footer.php"; ?>
