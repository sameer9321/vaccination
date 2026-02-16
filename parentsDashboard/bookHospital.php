<?php
session_start();
$pageTitle = "Book Hospital";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int) ($_SESSION["user_id"] ?? 0);
$username = (string) ($_SESSION["username"] ?? "Parent");
$parentId = (int) ($_SESSION["parent_id"] ?? 0);

/* Resolve parentId from parents table if missing */
if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    if ($rowU) {
        $email = $rowU['email'];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $email);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = $rowP['parent_id'];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please re-login.");
}

if (isset($_GET["delete"])) {
    $deleteId = (int) $_GET["delete"];
    mysqli_query($conn, "DELETE b FROM bookings b JOIN children c ON c.child_id = b.child_id WHERE b.id = $deleteId AND c.parent_id = $parentId");
    header("Location: bookHospital.php?deleted=1");
    exit;
}

if (isset($_POST["book"])) {
    $childId = (int) $_POST["child_id"];
    $hospitalId = (int) $_POST["hospital_id"];
    $vaccineName = mysqli_real_escape_string($conn, $_POST["vaccine_name"]);
    $bookingDate = $_POST["booking_date"];

    if ($bookingDate < date("Y-m-d")) {
        header("Location: bookHospital.php?past=1");
    } else {
        $stmtAdd = mysqli_prepare($conn, "INSERT INTO bookings (child_id, hospital_id, vaccine_name, booking_date, status) VALUES (?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmtAdd, "iiss", $childId, $hospitalId, $vaccineName, $bookingDate);
        mysqli_stmt_execute($stmtAdd);
        header("Location: bookHospital.php?booked=1");
    }
    exit;
}

$children = mysqli_query($conn, "SELECT child_id, child_name FROM children WHERE parent_id = $parentId ORDER BY child_name ASC");
$hospitals = mysqli_query($conn, "SELECT id, hospital_name FROM hospitals ORDER BY hospital_name ASC");
$bookings = mysqli_query($conn, "SELECT b.*, c.child_name, h.hospital_name FROM bookings b JOIN children c ON c.child_id = b.child_id JOIN hospitals h ON h.id = b.hospital_id WHERE c.parent_id = $parentId ORDER BY b.booking_date DESC");
$vaccines = mysqli_query($conn, "SELECT id, name FROM vaccines ORDER BY name ASC");

include "../base/header.php";
?>

<!--
  Responsive notes:
  - No sideways scrolling
  - Form and table stack nicely on mobile
  - On mobile, booking details show under Child column (other columns hidden)
  - Animations: twFadeUp entrance + hover transitions
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
                <span class="text-slate-700">Hospital Booking</span>
            </div>

            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Book Vaccination
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Create a new appointment and track your booking history.
            </p>
        </div>

        <div class="flex">
            <a href="parentdashboard.php"
               class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md active:translate-y-0">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET["booked"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i class="fa fa-check"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Booked</div>
                    <div class="mt-1 text-sm">Appointment booked successfully.</div>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET["past"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-rose-50 p-4 text-rose-800 ring-1 ring-rose-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Invalid date</div>
                    <div class="mt-1 text-sm">Please select a current or future date.</div>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET["deleted"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-amber-50 p-4 text-amber-800 ring-1 ring-amber-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Cancelled</div>
                    <div class="mt-1 text-sm">Booking has been cancelled.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- New appointment -->
        <div class="lg:col-span-4 twFadeUp">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">New Appointment</h3>
                        <p class="mt-1 text-sm text-slate-600">Pick child, hospital, vaccine, and date.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <i class="fa fa-calendar-plus-o"></i>
                    </div>
                </div>

                <div class="mt-5">
                    <?php if (mysqli_num_rows($children) === 0): ?>
                        <div class="rounded-2xl bg-sky-50 p-4 text-sky-900 ring-1 ring-sky-100">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                    <i class="fa fa-info"></i>
                                </div>
                                <div class="text-sm">
                                    Please <a class="font-semibold text-sky-700 underline" href="childDetails.php">add a child</a> first.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Select Child</label>
                                <select name="child_id"
                                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                        required>
                                    <option value="">Choose Child</option>
                                    <?php while ($c = mysqli_fetch_assoc($children)): ?>
                                        <option value="<?= (int)$c['child_id'] ?>"><?= htmlspecialchars($c['child_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">Select Hospital</label>
                                <select name="hospital_id"
                                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                        required>
                                    <option value="">Choose Hospital</option>
                                    <?php while ($h = mysqli_fetch_assoc($hospitals)): ?>
                                        <option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars($h['hospital_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">Select Vaccine</label>
                                <select name="vaccine_name"
                                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                        required>
                                    <option value="">Choose Vaccine</option>
                                    <?php while ($v = mysqli_fetch_assoc($vaccines)): ?>
                                        <option value="<?= htmlspecialchars($v['name']) ?>"><?= htmlspecialchars($v['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">Preferred Date</label>
                                <input type="date" name="booking_date"
                                       class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                       required min="<?= date('Y-m-d') ?>">
                            </div>

                            <button type="submit" name="book"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                                Confirm Booking
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Booking history -->
        <div class="lg:col-span-8 twFadeUp" style="animation-delay: 80ms;">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Booking History</h3>
                            <p class="mt-1 text-sm text-slate-600">Your past and upcoming appointments.</p>
                        </div>
                        <div class="text-sm text-slate-600">
                            (Newest first)
                        </div>
                    </div>
                </div>

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
                                <th class="px-4 py-3 text-right sm:px-6">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $i = 1;
                            if (mysqli_num_rows($bookings) > 0):
                                while ($b = mysqli_fetch_assoc($bookings)):
                                    $s = strtolower((string)$b['status']);
                                    $isOk = in_array($s, ['vaccinated','completed'], true);
                                    $isBad = ($s === 'rejected');
                                    $dateTxt = date('d M Y', strtotime($b['booking_date']));
                            ?>
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                                    <!-- Child (mobile shows details) -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($b['child_name']) ?></div>

                                        <div class="mt-1 text-xs text-slate-500 sm:hidden">
                                            Vaccine: <?= htmlspecialchars($b['vaccine_name']) ?>
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500 md:hidden">
                                            Hospital: <?= htmlspecialchars($b['hospital_name']) ?>
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500 lg:hidden">
                                            Date: <?= $dateTxt ?>
                                        </div>

                                        <div class="mt-2 lg:hidden">
                                            <?php if ($isBad): ?>
                                                <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                                    <?= strtoupper($b['status']) ?>
                                                </span>
                                            <?php elseif ($isOk): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                    <?= strtoupper($b['status']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                    <?= strtoupper($b['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Vaccine (sm+) -->
                                    <td class="hidden sm:table-cell px-4 py-4 sm:px-6">
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                            <?= htmlspecialchars($b['vaccine_name']) ?>
                                        </span>
                                    </td>

                                    <!-- Hospital (md+) -->
                                    <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                        <?= htmlspecialchars($b['hospital_name']) ?>
                                    </td>

                                    <!-- Date (lg+) -->
                                    <td class="hidden lg:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                        <?= $dateTxt ?>
                                    </td>

                                    <!-- Status (lg+) -->
                                    <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                                        <?php if ($isBad): ?>
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                                <?= strtoupper($b['status']) ?>
                                            </span>
                                        <?php elseif ($isOk): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                <?= strtoupper($b['status']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                <?= strtoupper($b['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <a href="bookHospital.php?delete=<?= (int)$b['id'] ?>"
                                           class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                           onclick="return confirm('Cancel this appointment?');">
                                            <i class="fa fa-trash sm:mr-2"></i>
                                            <span class="hidden sm:inline">Cancel</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-slate-600 sm:px-6">
                                        <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <div class="font-semibold text-slate-900">No appointments found.</div>
                                            <div class="text-sm text-slate-600">Book your first appointment using the form.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                    On mobile, details appear under the child name to fit the screen.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
