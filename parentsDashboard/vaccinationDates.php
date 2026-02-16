<?php
session_start();
$pageTitle = "Vaccination Dates";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$parentId = (int)($_SESSION["parent_id"] ?? 0);
if ($parentId <= 0) {
    die("Parent profile not found. Please log in again.");
}

$vaccinations = [];
$stmt = mysqli_prepare($conn, "
    SELECT
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        b.status,
        h.hospital_name
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    LEFT JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ? AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $parentId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $vaccinations[] = $row;
    }
    mysqli_stmt_close($stmt);
}

include "../base/header.php";
?>

<!--
  Responsive notes:
  - No sideways scrolling on mobile
  - On mobile: vaccine, hospital, date, status appear under child name
  - On desktop: full table columns
  - Animations: twFadeUp entrance + hover row transitions
-->

<div class="py-4">
    <!-- Header -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="parentdashboard.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                    <i class="fa fa-dashboard"></i>
                    <span>Parent</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700">Upcoming Dates</span>
            </div>

            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Vaccination Schedule
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Upcoming dates for your children.
            </p>
        </div>

        <div class="flex">
            <a href="bookHospital.php"
               class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                <i class="fa fa-plus mr-2"></i>
                Book New Appointment
            </a>
        </div>
    </div>

    <!-- Card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 m-0">Upcoming Vaccinations</h3>
                    <p class="mt-1 text-sm text-slate-600">Notifications of future dates.</p>
                </div>
                <div class="text-sm text-slate-600">
                    Total: <span class="font-semibold text-slate-900"><?= count($vaccinations) ?></span>
                </div>
            </div>
        </div>

        <!-- Table (no scroll, we hide columns on small screens) -->
        <div class="w-full">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">#</th>
                        <th class="px-4 py-3 sm:px-6">Child</th>
                        <th class="hidden sm:table-cell px-4 py-3 sm:px-6">Vaccine</th>
                        <th class="hidden md:table-cell px-4 py-3 sm:px-6">Hospital</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Date</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (count($vaccinations) > 0): ?>
                        <?php $i = 1; foreach ($vaccinations as $v): ?>
                            <?php
                                $statusRaw = (string)($v["status"] ?? "pending");
                                $status = strtolower($statusRaw);
                                $isDone = in_array($status, ["done", "completed", "vaccinated"], true);
                                $isBad  = in_array($status, ["cancelled", "rejected"], true);
                            ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                                <!-- Child (mobile shows all details) -->
                                <td class="px-4 py-4 sm:px-6">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($v["child_name"]) ?></div>

                                    <div class="mt-1 text-xs text-slate-500 sm:hidden">
                                        Vaccine: <?= htmlspecialchars($v["vaccine_name"]) ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500 md:hidden">
                                        Hospital: <?= htmlspecialchars($v["hospital_name"] ?? "Not Assigned") ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500 lg:hidden">
                                        Date: <?= date('d M, Y', strtotime($v["booking_date"])) ?>
                                    </div>

                                    <div class="mt-2 lg:hidden">
                                        <?php if ($isBad): ?>
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                                <?= strtoupper($statusRaw ?: "PENDING") ?>
                                            </span>
                                        <?php elseif ($isDone): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                <?= strtoupper($statusRaw ?: "PENDING") ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                <?= strtoupper($statusRaw ?: "PENDING") ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Vaccine (sm+) -->
                                <td class="hidden sm:table-cell px-4 py-4 sm:px-6">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                        <?= htmlspecialchars($v["vaccine_name"]) ?>
                                    </span>
                                </td>

                                <!-- Hospital (md+) -->
                                <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa fa-hospital-o text-slate-400"></i>
                                        <?= htmlspecialchars($v["hospital_name"] ?? "Not Assigned") ?>
                                    </span>
                                </td>

                                <!-- Date (lg+) -->
                                <td class="hidden lg:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                    <span class="font-semibold text-indigo-700">
                                        <?= date('d M, Y', strtotime($v["booking_date"])) ?>
                                    </span>
                                </td>

                                <!-- Status (lg+) -->
                                <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                                    <?php if ($isBad): ?>
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                            <?= strtoupper($statusRaw ?: "PENDING") ?>
                                        </span>
                                    <?php elseif ($isDone): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                            <?= strtoupper($statusRaw ?: "PENDING") ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                            <?= strtoupper($statusRaw ?: "PENDING") ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2 text-slate-600">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-calendar-times-o"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No upcoming vaccinations found.</div>
                                    <div class="text-sm text-slate-600">Book an appointment to see it here.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            On mobile, details are shown under the child name to fit the screen.
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
