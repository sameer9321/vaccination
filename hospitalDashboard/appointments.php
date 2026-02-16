<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);

if (isset($_POST["update_status"])) {
    $bid = (int)$_POST["booking_id"];
    $status = $_POST["status"];
    $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE id = ? AND hospital_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $status, $bid, $hospitalId);
    mysqli_stmt_execute($stmt);
    header("Location: appointments.php?updated=1");
    exit;
}

$view = $_GET['view'] ?? 'upcoming';
$dateFilter = ($view === 'all') ? "" : "AND b.booking_date >= CURDATE()";

$query = "SELECT b.*, c.child_name FROM bookings b
          JOIN children c ON b.child_id = c.child_id
          WHERE b.hospital_id = $hospitalId $dateFilter
          ORDER BY b.booking_date ASC";
$result = mysqli_query($conn, $query);

$pageTitle = "Appointments";
include "../base/header.php";
?>

<!--
  Responsive notes:
  - No horizontal scrolling
  - On mobile: date and status move under child name
  - Update form stacks on mobile
  - Buttons wrap nicely
-->

<div class="py-4">
    <!-- Header -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                Appointments
            </div>
            <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Appointments</h2>
            <p class="mt-1 text-sm text-slate-600">
                View upcoming slots and update vaccination status.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="appointments.php?view=upcoming"
               class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm ring-1 transition active:scale-[0.99]
               <?= $view!='all' ? 'bg-slate-900 text-white ring-slate-900 hover:bg-slate-800' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' ?>">
                Upcoming
            </a>
            <a href="appointments.php?view=all"
               class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm ring-1 transition active:scale-[0.99]
               <?= $view=='all' ? 'bg-slate-900 text-white ring-slate-900 hover:bg-slate-800' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' ?>">
                All History
            </a>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i class="fa fa-check"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">Saved</div>
                        <div class="mt-1 text-sm">Appointment status updated successfully.</div>
                    </div>
                </div>
                <a href="appointments.php?view=<?= htmlspecialchars($view) ?>"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-50 active:scale-[0.99]">
                    Dismiss
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <!-- Toolbar -->
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="w-full sm:max-w-md">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="aptSearch"
                           class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-100"
                           placeholder="Search child name...">
                </div>
            </div>

            <div class="text-sm text-slate-600">
                View:
                <span class="font-semibold text-slate-900"><?= $view === 'all' ? 'All History' : 'Upcoming' ?></span>
            </div>
        </div>

        <!-- Table (no scroll, we hide columns on small screens) -->
        <div class="w-full">
            <table class="min-w-full text-left text-sm" id="aptTable">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Child</th>
                        <th class="hidden sm:table-cell px-4 py-3 sm:px-6">Vaccine</th>
                        <th class="hidden md:table-cell px-4 py-3 sm:px-6">Date</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Status</th>
                        <th class="px-4 py-3 text-right sm:px-6">Update</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php while($row = mysqli_fetch_assoc($result)):
                        $st = strtolower((string)$row['status']);
                        $isPending = ($st === 'pending');
                    ?>
                    <tr class="transition hover:bg-slate-50">
                        <!-- Child (mobile shows vaccine, date, status under child name) -->
                        <td class="px-4 py-4 sm:px-6">
                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['child_name']) ?></div>

                            <div class="mt-1 text-xs text-slate-500 sm:hidden">
                                Vaccine: <?= htmlspecialchars($row['vaccine_name']) ?>
                            </div>
                            <div class="mt-1 text-xs text-slate-500 md:hidden">
                                Date: <?= htmlspecialchars($row['booking_date']) ?>
                            </div>
                            <div class="mt-1 text-xs sm:hidden">
                                <?php if ($isPending): ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                <?php elseif (in_array($st, ['vaccinated','completed'])): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Vaccine (sm+) -->
                        <td class="hidden sm:table-cell px-4 py-4 text-slate-700 sm:px-6">
                            <?= htmlspecialchars($row['vaccine_name']) ?>
                        </td>

                        <!-- Date (md+) -->
                        <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                            <?= htmlspecialchars($row['booking_date']) ?>
                        </td>

                        <!-- Status (lg+) -->
                        <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                            <?php if ($isPending): ?>
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            <?php elseif (in_array($st, ['vaccinated','completed'])): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Update form -->
                        <td class="px-4 py-4 text-right sm:px-6">
                            <form method="POST"
                                  class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end"
                                  onsubmit="return confirm('Update this status?');">
                                <input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">

                                <select name="status"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-100 sm:w-40">
                                    <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                    <option value="Vaccinated" <?= $row['status']=='Vaccinated'?'selected':'' ?>>Vaccinated</option>
                                    <option value="Completed" <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                                </select>

                                <button type="submit" name="update_status"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.99] sm:w-auto">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            On mobile, vaccine, date, and status appear under the child name to avoid sideways scrolling.
        </div>
    </div>
</div>

<script>
document.getElementById('aptSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#aptTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        rows[i].style.display = rows[i].cells[0].textContent.toUpperCase().includes(filter) ? "" : "none";
    }
});
</script>

<?php include "../base/footer.php"; ?>
