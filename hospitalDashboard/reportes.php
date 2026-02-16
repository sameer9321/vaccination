<?php
session_start();
$pageTitle = "Vaccination Reports";
include '../base/header.php';
include '../includes/db.php';

$hospitalId = $_SESSION['hospital_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

$whereClause = ($role === 'hospital') ? "WHERE b.hospital_id = $hospitalId" : "";

$query = "SELECT c.child_name, b.vaccine_name, b.booking_date, b.status
          FROM bookings b
          JOIN children c ON c.child_id = b.child_id
          $whereClause
          ORDER BY b.booking_date DESC";

$result = mysqli_query($conn, $query);
?>

<!--
  UI notes:
  - No sideways scrolling on mobile
  - On mobile: date + status move under child name
  - Print view: hides nav/sidebar, keeps simple layout
  - Animations: twFadeUp + hover transitions
-->

<style>
@media print {
  .no-print, .sidebar, .navbar { display: none !important; }
  .content { margin: 0 !important; padding: 0 !important; }
}
</style>

<section class="content">
  <div class="container-fluid py-4">

    <!-- Header -->
    <div class="twFadeUp no-print mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
          <span class="h-2 w-2 rounded-full bg-teal-500"></span>
          Vaccination reports
        </div>
        <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Vaccination Reports</h2>
        <p class="mt-1 text-sm text-slate-600">Detailed logs for administered vaccines.</p>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <div class="relative w-full sm:w-64">
          <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input
            type="text"
            id="reportSearch"
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-300 focus:ring-4 focus:ring-teal-100"
            placeholder="Search by child..."
          >
        </div>

        <button onclick="window.print()"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]">
          <i class="fa fa-print mr-2"></i>
          Print
        </button>

        <span class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">
          Records: <?= (int)mysqli_num_rows($result); ?>
        </span>
      </div>
    </div>

    <!-- Card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
      <div class="border-b border-slate-100 px-5 py-4 sm:px-6 no-print">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <h4 class="text-sm font-semibold text-slate-900 m-0">Detailed Logs</h4>
          <div class="text-xs text-slate-500">
            <?= ($role === 'hospital') ? 'Hospital view' : 'Admin view' ?>
          </div>
        </div>
      </div>

      <!-- Table (no scroll, we hide columns on small screens) -->
      <div class="w-full">
        <table class="min-w-full text-left text-sm" id="reportTable">
          <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
              <th class="px-4 py-3 sm:px-6">#</th>
              <th class="px-4 py-3 sm:px-6">Child</th>
              <th class="hidden sm:table-cell px-4 py-3 sm:px-6">Vaccine</th>
              <th class="hidden md:table-cell px-4 py-3 sm:px-6">Date</th>
              <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <?php
              $i = 1;
              if (mysqli_num_rows($result) > 0):
                while ($r = mysqli_fetch_assoc($result)):
                  $status = trim((string)$r['status']);
                  $statusLower = strtolower($status);
                  $isPending = ($statusLower === 'pending');
                  $isDone = in_array($statusLower, ['done','completed','vaccinated'], true);
            ?>
              <tr class="transition hover:bg-slate-50">
                <td class="px-4 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                <!-- Child cell shows extra details on mobile -->
                <td class="px-4 py-4 sm:px-6">
                  <div class="font-semibold text-slate-900"><?= htmlspecialchars($r['child_name']) ?></div>

                  <div class="mt-1 text-xs text-slate-500 sm:hidden">
                    Vaccine: <?= htmlspecialchars($r['vaccine_name'] ?? 'N/A') ?>
                  </div>

                  <div class="mt-1 text-xs text-slate-500 md:hidden">
                    Date: <?= date('M d, Y', strtotime($r['booking_date'])) ?>
                  </div>

                  <div class="mt-2 lg:hidden">
                    <?php if ($isPending): ?>
                      <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                        <?= htmlspecialchars($status ?: 'Unknown') ?>
                      </span>
                    <?php elseif ($isDone): ?>
                      <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <?= htmlspecialchars($status ?: 'Unknown') ?>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        <?= htmlspecialchars($status ?: 'Unknown') ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Vaccine (sm+) -->
                <td class="hidden sm:table-cell px-4 py-4 text-slate-700 sm:px-6">
                  <span class="font-semibold text-teal-700"><?= htmlspecialchars($r['vaccine_name'] ?? 'N/A') ?></span>
                </td>

                <!-- Date (md+) -->
                <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                  <?= date('M d, Y', strtotime($r['booking_date'])) ?>
                </td>

                <!-- Status (lg+) -->
                <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                  <?php if ($isPending): ?>
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                      <?= htmlspecialchars($status ?: 'Unknown') ?>
                    </span>
                  <?php elseif ($isDone): ?>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                      <?= htmlspecialchars($status ?: 'Unknown') ?>
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                      <?= htmlspecialchars($status ?: 'Unknown') ?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr>
                <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                  <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                      <i class="fa fa-file-text-o"></i>
                    </div>
                    <div class="font-semibold text-slate-900">No vaccination records found.</div>
                    <div class="text-slate-600">Once appointments exist, they will appear here.</div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6 no-print">
        On mobile, vaccine, date, and status show under the child name to fit the screen.
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('reportSearch').addEventListener('keyup', function() {
  let filter = this.value.toUpperCase();
  let rows = document.querySelector("#reportTable tbody").rows;
  for (let i = 0; i < rows.length; i++) {
    let childName = rows[i].cells[1].textContent.toUpperCase();
    rows[i].style.display = childName.includes(filter) ? "" : "none";
  }
});
</script>

<?php include '../base/footer.php'; ?>
