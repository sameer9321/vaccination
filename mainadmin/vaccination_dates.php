<?php
$pageTitle = "Upcoming Vaccinations";
include '../base/header.php';
include '../includes/db.php';

$query = "
    SELECT 
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        DATEDIFF(b.booking_date, CURDATE()) as days_remaining
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    WHERE b.booking_date >= CURDATE()
      AND (b.status IS NULL OR b.status NOT IN ('Completed', 'Vaccinated', 'Done'))
    ORDER BY b.booking_date ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<!--
  Responsive + no ugly page scrolling:
  - Uses Tailwind layout (like your updated dashboards)
  - Table is inside overflow-x-auto container only, so the page doesn't scroll sideways
  - Nice badges, urgent highlighting, small animations (twFadeUp)
-->

<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Admin view
                </div>

                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-calendar mr-2 text-sky-600"></i>
                    Upcoming Schedules
                </h2>
                <p class="mt-1 text-sm text-slate-600">All pending vaccinations with time remaining.</p>
            </div>

            <div class="inline-flex items-center gap-2 self-start sm:self-center">
                <span class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                    Total Upcoming: <?= mysqli_num_rows($result); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="twFadeUp mt-5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Child Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Vaccine Type</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Scheduled Date</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Time Remaining</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($result)):
                            $days = (int)($r['days_remaining'] ?? 0);
                            $timeLabel = ($days === 0) ? "Today" : (($days === 1) ? "Tomorrow" : $days . " days left");
                            $isUrgent = ($days <= 2);
                        ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($r['child_name']) ?></div>
                                </td>

                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                        <?= htmlspecialchars($r['vaccine_name']) ?>
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                                    <i class="fa fa-calendar-o mr-1 text-slate-400"></i>
                                    <?= date('M d, Y', strtotime($r['booking_date'])) ?>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <?php if ($isUrgent): ?>
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                            <i class="fa fa-clock-o mr-2"></i>
                                            <?= htmlspecialchars($timeLabel) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                            <i class="fa fa-clock-o mr-2"></i>
                                            <?= htmlspecialchars($timeLabel) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-info-circle"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No upcoming vaccinations scheduled.</div>
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

<?php include '../base/footer.php'; ?>
