<?php
$pageTitle = "Vaccination Reports";
include '../base/header.php';
include '../includes/db.php';

$whereCondition = '';
$startDate = $_POST['start_date'] ?? '';
$endDate   = $_POST['end_date'] ?? '';

/*
  Security + correctness:
  - Use prepared statements for date filters (instead of string concat)
  - Keep export and table using same query builder
*/
$params = [];
$types  = "";

if (!empty($startDate) && !empty($endDate)) {
    $whereCondition = " AND b.booking_date BETWEEN ? AND ? ";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

function fetchReports($conn, $whereCondition, $types, $params) {
    $sql = "
        SELECT c.child_name, b.vaccine_name, b.booking_date, b.status
        FROM bookings b
        JOIN children c ON c.child_id = b.child_id
        WHERE 1=1 $whereCondition
        ORDER BY b.booking_date DESC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [null, []];

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return [count($rows), $rows];
}

/* CSV Export */
if (isset($_POST['export_csv'])) {
    ob_end_clean();

    $csvFileName = 'vaccination_report_' . date('Y_m_d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $csvFileName . '"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['Child Name', 'Vaccine Name', 'Booking Date', 'Status']);

    [$total, $rows] = fetchReports($conn, $whereCondition, $types, $params);
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['child_name'] ?? '',
            $row['vaccine_name'] ?? '',
            $row['booking_date'] ?? '',
            $row['status'] ?? '',
        ]);
    }
    fclose($output);
    exit();
}

[$totalRecords, $rows] = fetchReports($conn, $whereCondition, $types, $params);
?>

<!--
  Responsive + no page scrolling:
  - Use container-fluid (same as your newer screens)
  - Filter controls stack on small screens
  - Table has only horizontal scroll inside its wrapper
  - Uses your Tailwind utility styling + twFadeUp animation (from header.php)
-->

<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Reports
                </div>

                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-file-text mr-2 text-sky-700"></i>
                    Vaccination Reports
                </h2>
                <p class="mt-1 text-sm text-slate-600">Filter by date range, then export if needed.</p>
            </div>

            <div class="inline-flex items-center gap-2 self-start sm:self-center">
                <span class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                    Total Records: <?= (int)$totalRecords ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="twFadeUp mt-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <form method="POST" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-semibold text-slate-700">From Date</label>
                <input
                    type="date"
                    name="start_date"
                    value="<?= htmlspecialchars($startDate) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                >
            </div>

            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-semibold text-slate-700">To Date</label>
                <input
                    type="date"
                    name="end_date"
                    value="<?= htmlspecialchars($endDate) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                >
            </div>

            <div class="lg:col-span-4">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]"
                    >
                        <i class="fa fa-filter mr-2"></i>
                        Filter
                    </button>

                    <button
                        type="submit"
                        name="export_csv"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]"
                    >
                        <i class="fa fa-download mr-2"></i>
                        Export CSV
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($startDate) && !empty($endDate)): ?>
            <div class="mt-4 text-xs text-slate-500">
                Showing results from <span class="font-semibold text-slate-700"><?= htmlspecialchars($startDate) ?></span>
                to <span class="font-semibold text-slate-700"><?= htmlspecialchars($endDate) ?></span>.
            </div>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="twFadeUp mt-5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Child Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Vaccine</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Date</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php
                    $i = 1;
                    if (!empty($rows)):
                        foreach ($rows as $r):
                            $statusRaw = (string)($r['status'] ?? 'pending');
                            $status = strtolower(trim($statusRaw));

                            $pill = "bg-slate-100 text-slate-700 ring-slate-200";
                            if ($status === "pending") $pill = "bg-amber-50 text-amber-700 ring-amber-100";
                            if (in_array($status, ["done","completed","vaccinated"], true)) $pill = "bg-emerald-50 text-emerald-700 ring-emerald-100";
                    ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($r['child_name'] ?? '') ?></div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <?= htmlspecialchars(($r['vaccine_name'] ?? '') !== '' ? $r['vaccine_name'] : 'N/A') ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                                <i class="fa fa-calendar-o mr-1 text-slate-400"></i>
                                <?= !empty($r['booking_date']) ? date('M d, Y', strtotime($r['booking_date'])) : 'N/A' ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 <?= $pill ?>">
                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-info-circle"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No records found</div>
                                    <div class="text-slate-600">Try a different date range.</div>
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

<?php include '../base/footer.php'; ?>
