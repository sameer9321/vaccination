<?php
$pageTitle = "Parent Requests";
include '../base/header.php';
include '../includes/db.php';

if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);
    mysqli_query($conn,"UPDATE hospital_requests SET status='Approved' WHERE id=$id");
    header("Location: requests.php?msg=approved");
    exit;
}

if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);
    mysqli_query($conn,"UPDATE hospital_requests SET status='Rejected' WHERE id=$id");
    header("Location: requests.php?msg=rejected");
    exit;
}

$query = "SELECT hr.*, p.parent_name
          FROM hospital_requests hr
          JOIN parents p ON hr.parent_id = p.parent_id
          WHERE hr.status='Pending'
          ORDER BY hr.id DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
?>

<!--
  Responsive + no page scrolling:
  - Table is wrapped with overflow-x-auto only (page won't sideways scroll)
  - Clean badges and action buttons with hover/active transitions
  - twFadeUp class assumes you added it in header.php already (like your other updated screens)
-->

<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Admin queue
                </div>

                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-envelope-open mr-2 text-amber-600"></i>
                    Pending Parent Requests
                </h2>
                <p class="mt-1 text-sm text-slate-600">Approve or reject association requests.</p>
            </div>

            <div class="inline-flex items-center gap-2 self-start sm:self-center">
                <span class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                    Total Pending: <?= mysqli_num_rows($result); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="twFadeUp mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-700 ring-1 ring-emerald-200">
                        <i class="fa fa-check"></i>
                    </div>
                    <div>
                        <div class="font-semibold">Done</div>
                        <div class="text-emerald-800/80">
                            Request successfully <?= htmlspecialchars($_GET['msg']); ?>.
                        </div>
                    </div>
                </div>
                <a href="requests.php"
                   class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                    Dismiss
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="twFadeUp mt-5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Parent Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Hospital Requested</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Current Status</th>
                        <th class="whitespace-nowrap px-5 py-3 text-center sm:px-6">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php
                    $i = 1;
                    if(mysqli_num_rows($result) > 0):
                        while($r = mysqli_fetch_assoc($result)):
                    ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($r['parent_name']) ?></div>
                                <div class="mt-1 text-xs text-slate-500">Request ID: #<?= (int)$r['id'] ?></div>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="inline-flex items-center rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <i class="fa fa-hospital-o mr-2 text-slate-500"></i>
                                    Hospital ID: <?= htmlspecialchars($r['hospital_id']) ?>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                    <span class="mr-2 h-2 w-2 rounded-full bg-amber-500"></span>
                                    Pending
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-center sm:px-6">
                                <div class="inline-flex flex-wrap items-center justify-center gap-2">
                                    <a href="?approve=<?= (int)$r['id'] ?>"
                                       class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]">
                                        <i class="fa fa-check mr-2"></i>
                                        Approve
                                    </a>

                                    <a href="?reject=<?= (int)$r['id'] ?>"
                                       class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700 active:scale-[0.99]"
                                       onclick="return confirm('Are you sure you want to reject this request?')">
                                        <i class="fa fa-times mr-2"></i>
                                        Reject
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-folder-open-o"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No pending requests found</div>
                                    <div class="text-slate-600">All parent requests have been processed.</div>
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
