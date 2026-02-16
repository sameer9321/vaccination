<?php
session_start();
$pageTitle = "Update Vaccine Status";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);
if ($hospitalId <= 0) {
    die("Hospital session expired. Please log in again.");
}

function calc_age_text($birthDate) {
    if (!$birthDate) return "N/A";
    try {
        $dob = new DateTime($birthDate);
        $diff = (new DateTime())->diff($dob);
        return ($diff->y > 0) ? $diff->y . " yrs" : (($diff->m > 0) ? $diff->m . " mos" : $diff->d . " days");
    } catch (Exception $e) { return "N/A"; }
}

if (isset($_POST["status_update"])) {
    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $status = trim((string)($_POST["status"] ?? ""));
    $allowed = ["Pending", "Vaccinated", "Not Vaccinated", "Completed"];

    if ($bookingId > 0 && in_array($status, $allowed)) {
        $stmtUp = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?");
        mysqli_stmt_bind_param($stmtUp, "sii", $status, $bookingId, $hospitalId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        header("Location: updateVaccineStatus.php?updated=1");
        exit;
    }
}

$rows = [];
$stmt = mysqli_prepare($conn, "
    SELECT b.id AS booking_id, c.child_name, c.birth_date, b.vaccine_name, b.booking_date, b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.hospital_id = ?
    ORDER BY b.booking_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $hospitalId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<!--
  UI notes:
  - Clean hospital theme: teal + slate
  - Animations:
    - twFadeUp (from header.php) for entrance
    - hover row highlight, button transitions
  - Responsive:
    - header stacks on mobile
    - table scrolls horizontally on small screens
-->

<div class="py-4">
    <!-- Page header -->
    <div class="twFadeUp mb-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                    <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                    Vaccination records
                </div>
                <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                    Update Vaccine Status
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Manage and update patient statuses.
                </p>
            </div>

            <div class="text-sm text-slate-600">
                Showing <span class="font-semibold text-slate-900"><?= count($rows) ?></span> total appointments
            </div>
        </div>
    </div>

    <?php if (isset($_GET["updated"])): ?>
        <!-- Success toast-like alert -->
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i class="fa fa-check"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">Updated</div>
                        <div class="mt-1 text-sm">Patient record has been modified.</div>
                    </div>
                </div>
                <a href="updateVaccineStatus.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-50 active:scale-[0.99]">
                    Dismiss
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <!-- Toolbar -->
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="w-full sm:max-w-md">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        id="tableSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-100"
                        placeholder="Search by child or vaccine name..."
                    >
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Tip: Search matches child name and vaccine name.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                    <i class="fa fa-filter mr-2 text-slate-500"></i>
                    Filter: search
                </span>
            </div>
        </div>

        <!-- Table -->
        <div class="w-full">
            <table class="min-w-full text-left text-sm" id="statusTable">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
    <tr>
        <th class="px-4 py-3">Child</th>
        <th class="px-4 py-3 hidden md:table-cell">Age</th>
        <th class="px-4 py-3">Vaccine</th>
        <th class="px-4 py-3 hidden sm:table-cell">Date</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3 text-right">Action</th>
    </tr>
</thead>


                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($rows)): foreach ($rows as $row):
                        $st = strtolower($row["status"]);
                        $isFocused = (isset($_GET['focus']) && $_GET['focus'] == $row['booking_id']);
                    ?>
                        <!-- Focus row highlight + hover effect -->
                       <tr class="transition hover:bg-slate-50">
    <td class="px-4 py-4">
        <div class="font-semibold text-slate-900"><?= htmlspecialchars($row["child_name"]) ?></div>

        <!-- Show hidden info under name on mobile -->
        <div class="mt-1 text-xs text-slate-500 md:hidden">
            Age: <?= calc_age_text($row["birth_date"]) ?>
        </div>

        <div class="mt-1 text-xs text-slate-500 sm:hidden">
            Date: <?= date('d M Y', strtotime($row["booking_date"])) ?>
        </div>
    </td>

    <td class="hidden md:table-cell px-4 py-4 text-slate-700">
        <?= calc_age_text($row["birth_date"]) ?>
    </td>

    <td class="px-4 py-4">
        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
            <?= htmlspecialchars($row["vaccine_name"]) ?>
        </span>
    </td>

    <td class="hidden sm:table-cell px-4 py-4 text-slate-700">
        <?= date('d M Y', strtotime($row["booking_date"])) ?>
    </td>

    <td class="px-4 py-4">
        <?php if ($st === "pending"): ?>
            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                <?= strtoupper($row["status"]) ?>
            </span>
        <?php elseif (in_array($st, ["vaccinated","done","completed"])): ?>
            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                <?= strtoupper($row["status"]) ?>
            </span>
        <?php else: ?>
            <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                <?= strtoupper($row["status"]) ?>
            </span>
        <?php endif; ?>
    </td>

    <td class="px-4 py-4">
        <form method="post"
              class="flex flex-col gap-2 sm:flex-row sm:items-center"
              onsubmit="return confirm('Update this status?');">

            <input type="hidden" name="booking_id" value="<?= $row["booking_id"] ?>">

            <select name="status"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-teal-300 focus:ring-4 focus:ring-teal-100 sm:w-40">
                <?php
                foreach (["Pending","Vaccinated","Not Vaccinated","Completed"] as $opt) {
                    $sel = ($opt === $row["status"]) ? "selected" : "";
                    echo "<option value='$opt' $sel>$opt</option>";
                }
                ?>
            </select>

            <button type="submit" name="status_update"
                    class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 active:scale-[0.98]">
                Update
            </button>
        </form>
    </td>
</tr>

                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-folder-open"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No appointments found.</div>
                                    <div class="text-slate-600">Once bookings exist, they will appear here.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            Tip: On mobile, swipe sideways to see all columns.
        </div>
    </div>
</div>

<script>
document.getElementById('tableSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#statusTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let firstCol = rows[i].cells[0].textContent.toUpperCase();
        let thirdCol = rows[i].cells[2].textContent.toUpperCase();
        if (firstCol.indexOf(filter) > -1 || thirdCol.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
});
</script>

<?php include "../base/footer.php"; ?>
