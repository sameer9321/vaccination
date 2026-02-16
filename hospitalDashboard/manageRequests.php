<?php
session_start();
$pageTitle = "Manage Association Requests";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "hospital") {
    header("Location: ../index.php");
    exit;
}

$hospitalId = (int)($_SESSION["hospital_id"] ?? 0);
$hospitalName = $_SESSION["hospital_name"] ?? "";

if ($hospitalId <= 0) {
    die("Hospital session not found. Please re-login.");
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $requestId = (int)$_GET['id'];
    $action = $_GET['action'];
    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

    $stmt = mysqli_prepare($conn, "UPDATE hospital_requests SET status = ? WHERE id = ? AND requested_hospital = ?");
    mysqli_stmt_bind_param($stmt, "sis", $newStatus, $requestId, $hospitalName);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: manageRequests.php?msg=" . ($action === 'approve' ? 'approved' : 'rejected'));
    } else {
        header("Location: manageRequests.php?msg=error");
    }
    exit;
}

$requests = [];
$query = "SELECT r.*, c.child_name, c.birth_date, p.parent_name, p.email
          FROM hospital_requests r
          JOIN children c ON r.child_id = c.child_id
          JOIN parents p ON r.parent_id = p.parent_id
          WHERE r.requested_hospital = ?
          ORDER BY r.id DESC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $hospitalName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    mysqli_stmt_close($stmt);
}
include "../base/header.php";
?>

<!--
  UI notes:
  - No horizontal scrolling: we hide less important columns on small screens
  - Mobile layout: child cell shows parent + email under it
  - Buttons stack on mobile, inline on sm and up
  - Animations: twFadeUp for entrance + hover transitions
-->

<div class="py-4">
    <!-- Page header -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">
                <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                Association requests
            </div>
            <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Requests for <span class="text-teal-700"><?= htmlspecialchars($hospitalName) ?></span>
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Approve or reject requests from parents.
            </p>
        </div>

        <div class="text-sm text-slate-600">
            Total: <span class="font-semibold text-slate-900"><?= count($requests) ?></span>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php
          $msg = (string)$_GET['msg'];
          $isOk = in_array($msg, ['approved','rejected']);
        ?>
        <div class="twFadeUp mb-4 rounded-2xl p-4 ring-1 <?= $isOk ? 'bg-emerald-50 text-emerald-800 ring-emerald-100' : 'bg-rose-50 text-rose-800 ring-rose-100' ?>">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl <?= $isOk ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                        <i class="fa <?= $isOk ? 'fa-check' : 'fa-exclamation-triangle' ?>"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">
                            <?= $isOk ? 'Action processed' : 'Something went wrong' ?>
                        </div>
                        <div class="mt-1 text-sm">
                            <?= $isOk ? 'Your request update was saved.' : 'Please try again.' ?>
                        </div>
                    </div>
                </div>

                <a href="manageRequests.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold shadow-sm ring-1 transition hover:bg-slate-50 active:scale-[0.99] <?= $isOk ? 'text-emerald-700 ring-emerald-200' : 'text-rose-700 ring-rose-200' ?>">
                    Dismiss
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main table card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-semibold text-slate-900">Requests</div>
                <div class="text-xs text-slate-500">Most recent shown first</div>
            </div>
        </div>

        <div class="w-full">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">Child</th>
                        <th class="hidden md:table-cell px-4 py-3 sm:px-6">Parent</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Contact (Email)</th>
                        <th class="px-4 py-3 sm:px-6">Status</th>
                        <th class="px-4 py-3 text-right sm:px-6">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($requests)): foreach ($requests as $r):
                        $statusLower = strtolower((string)$r['status']);
                        $isPending = ($statusLower === 'pending');
                    ?>
                        <tr class="transition hover:bg-slate-50">
                            <!-- Child (plus parent details on mobile) -->
                            <td class="px-4 py-4 sm:px-6">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($r['child_name']) ?></div>

                                <!-- Mobile details (no scroll) -->
                                <div class="mt-1 text-xs text-slate-500 md:hidden">
                                    Parent: <?= htmlspecialchars($r['parent_name']) ?>
                                </div>
                                <div class="mt-1 text-xs text-slate-500 lg:hidden">
                                    Email: <?= htmlspecialchars($r['email']) ?>
                                </div>
                            </td>

                            <!-- Parent (md+) -->
                            <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                <?= htmlspecialchars($r['parent_name']) ?>
                            </td>

                            <!-- Email (lg+) -->
                            <td class="hidden lg:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                <?= htmlspecialchars($r['email']) ?>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-4 sm:px-6">
                                <?php if ($isPending): ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                        <?= strtoupper($r['status']) ?>
                                    </span>
                                <?php elseif ($statusLower === 'approved'): ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <?= strtoupper($r['status']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                        <?= strtoupper($r['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-4 text-right sm:px-6">
                                <?php if ($isPending): ?>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        <a href="manageRequests.php?id=<?= (int)$r['id'] ?>&action=approve"
                                           class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]">
                                            <i class="fa fa-check mr-2"></i>
                                            Approve
                                        </a>
                                        <a href="manageRequests.php?id=<?= (int)$r['id'] ?>&action=reject"
                                           class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 active:scale-[0.99]">
                                            <i class="fa fa-times mr-2"></i>
                                            Reject
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-inbox"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No requests found.</div>
                                    <div class="text-slate-600">When parents request association, they will appear here.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            On small screens, parent and email details appear under the child name to avoid sideways scrolling.
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
