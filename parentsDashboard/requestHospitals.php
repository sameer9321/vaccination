<?php
session_start();
$pageTitle = "Request Hospital";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int) ($_SESSION["user_id"] ?? 0);
$username = (string) ($_SESSION["username"] ?? "");
$parentId = (int) ($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    if ($rowU) {
        $userEmail = $rowU["email"];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $userEmail);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = (int) $rowP["parent_id"];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please re-login.");
}

/* Delete request logic */
if (isset($_GET["delete"])) {
    $deleteId = (int) $_GET["delete"];
    $stmtDel = mysqli_prepare($conn, "DELETE FROM hospital_requests WHERE id = ? AND parent_id = ?");
    mysqli_stmt_bind_param($stmtDel, "ii", $deleteId, $parentId);
    mysqli_stmt_execute($stmtDel);
    mysqli_stmt_close($stmtDel);
    header("Location: requestHospitals.php?deleted=1");
    exit;
}

$success = false;

if (isset($_POST["submit_request"])) {
    $childId = (int) $_POST["child_id"];
    $requestedHospital = trim($_POST["requested_hospital"]);

    if ($childId > 0 && !empty($requestedHospital)) {
        $status = "Pending";

        $stmtAdd = mysqli_prepare(
            $conn,
            "INSERT INTO hospital_requests (parent_id, child_id, requested_hospital, status)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmtAdd, "iiss", $parentId, $childId, $requestedHospital, $status);
        mysqli_stmt_execute($stmtAdd);
        mysqli_stmt_close($stmtAdd);
        $success = true;
    }
}

/* Fetch Children */
$children = [];
$resC = mysqli_query($conn, "SELECT child_id, child_name FROM children WHERE parent_id = $parentId ORDER BY child_name ASC");
while ($rowC = mysqli_fetch_assoc($resC)) $children[] = $rowC;

$hospitalList = mysqli_query($conn, "SELECT hospital_name FROM hospitals ORDER BY hospital_name ASC");

$requests = [];
$resR = mysqli_query($conn, "SELECT r.*, c.child_name FROM hospital_requests r JOIN children c ON c.child_id = r.child_id WHERE r.parent_id = $parentId ORDER BY r.id DESC");
while ($rowR = mysqli_fetch_assoc($resR)) $requests[] = $rowR;

include "../base/header.php";
?>

<!--
  Responsive notes:
  - No sideways scrolling
  - On mobile: hospital, status, date show under child name
  - Buttons stack on mobile
  - Animations: twFadeUp entrance + hover transitions
-->

<div class="py-4">
    <!-- Header -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="parentdashboard.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                    <i class="fa fa-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700">Hospital Requests</span>
            </div>

            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Hospital Association
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Request to link your child to a specific hospital.
            </p>
        </div>

        <div class="flex">
            <a href="parentdashboard.php"
               class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-200 transition hover:bg-indigo-50 active:scale-[0.99]">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i class="fa fa-check"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">Request sent</div>
                        <div class="mt-1 text-sm">Your request has been submitted successfully.</div>
                    </div>
                </div>
                <a href="requestHospitals.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-50 active:scale-[0.99]">
                    Dismiss
                </a>
            </div>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-amber-50 p-4 text-amber-800 ring-1 ring-amber-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Request removed</div>
                    <div class="mt-1 text-sm">The association request has been deleted.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- New request -->
        <div class="lg:col-span-4 twFadeUp">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">New Request</h3>
                        <p class="mt-1 text-sm text-slate-600">Submit a new hospital association request.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                </div>

                <form method="post" class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Select Child</label>
                        <select name="child_id"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                required>
                            <option value="">Choose Child</option>
                            <?php foreach ($children as $c): ?>
                                <option value="<?= (int)$c['child_id'] ?>"><?= htmlspecialchars($c['child_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Requested Hospital</label>
                        <input type="text" name="requested_hospital"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               placeholder="Type hospital name..." required>
                        <div class="mt-2 text-xs text-slate-500">
                            Tip: Type the exact hospital name used in the system.
                        </div>
                    </div>

                    <button type="submit" name="submit_request"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                        <i class="fa fa-paper-plane mr-2"></i>
                        Submit Request
                    </button>
                </form>
            </div>
        </div>

        <!-- History -->
        <div class="lg:col-span-8 twFadeUp" style="animation-delay: 80ms;">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Request History</h3>
                            <p class="mt-1 text-sm text-slate-600">Track requests you’ve submitted.</p>
                        </div>
                        <span class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">
                            Total: <?= count($requests) ?>
                        </span>
                    </div>
                </div>

                <!-- Table without sideways scrolling -->
                <div class="w-full">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3 sm:px-6">Child</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-6">Hospital</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-6">Status</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Submitted</th>
                                <th class="px-4 py-3 text-right sm:px-6">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $r): ?>
                                    <?php
                                        $stRaw = (string)$r['status'];
                                        $st = strtolower($stRaw);
                                        $isOk = in_array($st, ['approved','accepted'], true);
                                        $isBad = ($st === 'rejected');
                                        $isPending = ($st === 'pending');
                                    ?>
                                    <tr class="transition hover:bg-slate-50">
                                        <!-- Child (mobile shows all details) -->
                                        <td class="px-4 py-4 sm:px-6">
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($r["child_name"]) ?></div>

                                            <div class="mt-1 text-xs text-slate-500 sm:hidden">
                                                Hospital: <?= htmlspecialchars($r["requested_hospital"]) ?>
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500 md:hidden">
                                                Submitted: <?= date('M d, Y', strtotime($r["created_at"])) ?>
                                            </div>

                                            <div class="mt-2 md:hidden">
                                                <?php if ($isBad): ?>
                                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                                        <?= htmlspecialchars($stRaw) ?>
                                                    </span>
                                                <?php elseif ($isOk): ?>
                                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                        <?= htmlspecialchars($stRaw) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                        <?= htmlspecialchars($stRaw ?: 'Pending') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Hospital (sm+) -->
                                        <td class="hidden sm:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                            <?= htmlspecialchars($r["requested_hospital"]) ?>
                                        </td>

                                        <!-- Status (md+) -->
                                        <td class="hidden md:table-cell px-4 py-4 sm:px-6">
                                            <?php if ($isBad): ?>
                                                <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                                                    <?= htmlspecialchars($stRaw) ?>
                                                </span>
                                            <?php elseif ($isOk): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                    <?= htmlspecialchars($stRaw) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                    <?= htmlspecialchars($stRaw ?: 'Pending') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Submitted (lg+) -->
                                        <td class="hidden lg:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                            <?= date('M d, Y', strtotime($r["created_at"])) ?>
                                        </td>

                                        <!-- Action -->
                                        <td class="px-4 py-4 text-right sm:px-6">
                                            <a href="requestHospitals.php?delete=<?= (int)$r['id'] ?>"
                                               class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                               title="Cancel Request"
                                               onclick="return confirm('Are you sure you want to cancel this request?');">
                                                <i class="fa fa-trash sm:mr-2"></i>
                                                <span class="hidden sm:inline">Cancel</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-slate-600 sm:px-6">
                                        <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                                <i class="fa fa-folder-open-o"></i>
                                            </div>
                                            <div class="font-semibold text-slate-900">No association requests found.</div>
                                            <div class="text-sm text-slate-600">Submit a request to see it here.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                    On mobile, hospital, status, and date show under the child name to fit the screen.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
