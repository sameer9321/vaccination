<?php
session_start();
$pageTitle = "Hospital Dashboard";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "hospital") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");
$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

if ($hospitalId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = mysqli_fetch_assoc($resU);
        $userEmail = $rowU["email"] ?? "";
        mysqli_stmt_close($stmtU);

        if ($userEmail !== "") {
            $stmtH = mysqli_prepare($conn, "SELECT id, hospital_name FROM hospitals WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtH, "s", $userEmail);
            mysqli_stmt_execute($stmtH);
            $resH = mysqli_stmt_get_result($stmtH);
            if ($rowH = mysqli_fetch_assoc($resH)) {
                $hospitalId = (int)$rowH["id"];
                $_SESSION["hospital_id"] = $hospitalId;
                $_SESSION["hospital_name"] = $rowH["hospital_name"];
            }
            mysqli_stmt_close($stmtH);
        }
    }
}

if ($hospitalId <= 0) {
    die("Hospital account not linked. Please log out and log in again.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "N/A";
    $dob = new DateTime($birthDate);
    $diff = (new DateTime())->diff($dob);
    return ($diff->y > 0) ? $diff->y . " yrs" : (($diff->m > 0) ? $diff->m . " mos" : $diff->d . " days");
}

/* Statistics */
$totalAppointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE hospital_id = $hospitalId"))['total'];
$totalPending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE hospital_id = $hospitalId AND LOWER(status) = 'pending'"))['total'];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM hospital_requests WHERE requested_hospital = (SELECT hospital_name FROM hospitals WHERE id = $hospitalId) AND status = 'Pending'"))['total'];

$appointments = [];
$resA = mysqli_query($conn, "SELECT b.id as booking_id, c.child_name, c.birth_date, b.vaccine_name, b.booking_date, b.status
    FROM bookings b JOIN children c ON c.child_id = b.child_id
    WHERE b.hospital_id = $hospitalId AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC LIMIT 10");
while ($rowA = mysqli_fetch_assoc($resA)) $appointments[] = $rowA;

include "../base/header.php";
?>

<!--
  UI notes:
  - Hospital theme uses teal/sky accents (clean, clinical).
  - Animations:
    - twFadeUp class (from header.php) on sections
    - hover lift, shadow, active scale on buttons/cards
  - Responsiveness:
    - Header becomes stacked on mobile
    - KPI grid adapts 1 -> 2 -> 3 columns
    - Table stays scrollable on small screens
-->

<div class="py-4">
    <!-- Breadcrumb / Title -->
    <div class="twFadeUp mb-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="hospitalDashboard.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                        <i class="fa fa-hospital-o"></i>
                        <span>Hospital</span>
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Overview</span>
                </div>
                <h1 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                    Dashboard
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="appointments.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md active:translate-y-0">
                    View schedule
                </a>
            </div>
        </div>
    </div>

    <!-- Welcome / Stats -->
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                    <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                    Provider portal
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    Welcome, <?= htmlspecialchars($_SESSION["hospital_name"] ?? "Provider") ?>
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Healthcare facility portal for vaccine management.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[560px]">
                <div class="group relative overflow-hidden rounded-2xl bg-slate-50 p-4 text-center ring-1 ring-slate-200 transition hover:bg-white hover:shadow-sm">
                    <div class="text-2xl font-extrabold text-slate-900"><?= $totalAppointments ?></div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Total Slots</div>
                    <div class="mt-3 h-px bg-slate-200"></div>
                    <div class="mt-3 text-xs text-slate-600">All bookings</div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl bg-slate-50 p-4 text-center ring-1 ring-slate-200 transition hover:bg-white hover:shadow-sm">
                    <div class="text-2xl font-extrabold text-amber-600"><?= $totalPending ?></div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</div>
                    <div class="mt-3 h-px bg-slate-200"></div>
                    <div class="mt-3 text-xs text-slate-600">Needs updates</div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl bg-slate-50 p-4 text-center ring-1 ring-slate-200 transition hover:bg-white hover:shadow-sm">
                    <div class="text-2xl font-extrabold text-teal-700"><?= $totalRequests ?></div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">New Requests</div>
                    <div class="mt-3 h-px bg-slate-200"></div>
                    <div class="mt-3 text-xs text-slate-600">Associations</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="twFadeUp" style="animation-delay: 70ms;">
            <div class="group h-full rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 ring-1 ring-teal-100 transition group-hover:bg-teal-600 group-hover:text-white group-hover:ring-teal-600">
                    <i class="fa fa-check-square-o text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Update Status</h3>
                <p class="mt-1 text-sm text-slate-600">Process completed vaccinations.</p>
                <a href="updateVaccineStatus.php"
                   class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.99]">
                    Go to Updates
                </a>
            </div>
        </div>

        <div class="twFadeUp" style="animation-delay: 140ms;">
            <div class="group h-full rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 transition group-hover:bg-emerald-600 group-hover:text-white group-hover:ring-emerald-600">
                    <i class="fa fa-users text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Parent Requests</h3>
                <p class="mt-1 text-sm text-slate-600">Manage new hospital associations.</p>
                <a href="manageRequests.php"
                   class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-50 active:scale-[0.99]">
                    View (<?= $totalRequests ?>)
                </a>
            </div>
        </div>

        <div class="twFadeUp" style="animation-delay: 210ms;">
            <div class="group h-full rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 transition group-hover:bg-amber-600 group-hover:text-white group-hover:ring-amber-600">
                    <i class="fa fa-calendar-check-o text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Schedules</h3>
                <p class="mt-1 text-sm text-slate-600">Full appointment calendar view.</p>
                <a href="appointments.php"
                   class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm ring-1 ring-amber-200 transition hover:bg-amber-50 active:scale-[0.99]">
                    View All
                </a>
            </div>
        </div>

        <div class="twFadeUp" style="animation-delay: 280ms;">
            <div class="group h-full rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 ring-1 ring-sky-100 transition group-hover:bg-sky-600 group-hover:text-white group-hover:ring-sky-600">
                    <i class="fa fa-file-pdf-o text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Medical Reports</h3>
                <p class="mt-1 text-sm text-slate-600">Generate and upload patient records.</p>
                <a href="reportes.php"
                   class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-sky-700 shadow-sm ring-1 ring-sky-200 transition hover:bg-sky-50 active:scale-[0.99]">
                    View Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Upcoming table -->
    <div class="mt-6 twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="min-w-0">
                <h6 class="text-sm font-semibold text-slate-900">
                    Upcoming <span class="text-teal-700">Vaccination Slots</span>
                </h6>
                <p class="mt-1 text-sm text-slate-600">Next scheduled bookings for your facility.</p>
            </div>

            <a href="appointments.php"
               class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]">
                View All
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Child Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Current Age</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Vaccine</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Scheduled Date</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Status</th>
                        <th class="whitespace-nowrap px-5 py-3 text-right sm:px-6">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($appointments)): foreach ($appointments as $row):
                        $st = strtolower($row['status']);
                        $isPending = ($st === 'pending');
                    ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 sm:px-6">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($row["child_name"]) ?></div>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= calc_age_text($row["birth_date"]) ?></td>

                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                    <?= htmlspecialchars($row["vaccine_name"]) ?>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                                <?= date('d M, Y', strtotime($row["booking_date"])) ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <?php if ($isPending): ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                        <?= strtoupper($row["status"]) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <?= strtoupper($row["status"]) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                <a href="updateVaccineStatus.php?id=<?= $row['booking_id'] ?>"
                                   class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.99]">
                                    Update
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No appointments scheduled today.</div>
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
</div>

<?php include "../base/footer.php"; ?>
