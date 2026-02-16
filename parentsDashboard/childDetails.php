<?php
session_start();
$pageTitle = "Child Details";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int) ($_SESSION["user_id"] ?? 0);
$username = (string) ($_SESSION["username"] ?? "Parent");
$parentId = (int) ($_SESSION["parent_id"] ?? 0);

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
    $checkB = mysqli_query($conn, "SELECT id FROM bookings WHERE child_id = $deleteId LIMIT 1");
    if (mysqli_num_rows($checkB) > 0) {
        header("Location: childDetails.php?cannotdelete=1");
    } else {
        mysqli_query($conn, "DELETE FROM children WHERE child_id = $deleteId AND parent_id = $parentId");
        header("Location: childDetails.php?deleted=1");
    }
    exit;
}

if (isset($_POST["add_child"])) {
    $childName = mysqli_real_escape_string($conn, $_POST["child_name"]);
    $birthDate = $_POST["birth_date"];
    $vaccStatus = $_POST["vaccination_status"];

    $stmtAdd = mysqli_prepare($conn, "INSERT INTO children (parent_id, child_name, birth_date, vaccination_status) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmtAdd, "isss", $parentId, $childName, $birthDate, $vaccStatus);
    mysqli_stmt_execute($stmtAdd);
    header("Location: childDetails.php?added=1");
    exit;
}

$childrenResult = mysqli_query($conn, "SELECT * FROM children WHERE parent_id = $parentId ORDER BY child_id DESC");

include "../base/header.php";

function calc_age_text($birthDate)
{
    if (!$birthDate) return "N/A";
    $dob = new DateTime($birthDate);
    $now = new DateTime();
    $diff = $now->diff($dob);
    if ($diff->y > 0) return $diff->y . " yrs";
    if ($diff->m > 0) return $diff->m . " months";
    return $diff->d . " days";
}
?>

<!--
  Responsive notes:
  - No sideways scrolling on mobile
  - Table hides less important columns on small screens
  - Birth date and age move under child name on mobile
  - Form becomes a clean card with focus rings + spacing
  - Animations: twFadeUp entrance + hover lift on cards/buttons
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
                <span class="text-slate-700">Manage Children</span>
            </div>
            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Child Details</h2>
            <p class="mt-1 text-sm text-slate-600">Add and manage your children profiles.</p>
        </div>

        <div class="flex">
            <a href="parentdashboard.php"
               class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md active:translate-y-0">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET["added"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i class="fa fa-check"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Saved</div>
                    <div class="mt-1 text-sm">Child profile added successfully.</div>
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
                    <div class="text-sm font-semibold">Deleted</div>
                    <div class="mt-1 text-sm">Child record removed.</div>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET["cannotdelete"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-rose-50 p-4 text-rose-800 ring-1 ring-rose-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Cannot delete</div>
                    <div class="mt-1 text-sm">This child has active vaccine bookings.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Add child -->
        <div class="lg:col-span-4 twFadeUp">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Add New Child</h3>
                        <p class="mt-1 text-sm text-slate-600">Register a new child profile.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <i class="fa fa-plus"></i>
                    </div>
                </div>

                <form method="post" class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Child Full Name</label>
                        <input type="text" name="child_name"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               placeholder="Enter name" required>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Birth Date</label>
                        <input type="date" name="birth_date"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Initial Status</label>
                        <select name="vaccination_status"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="Pending">Pending</option>
                            <option value="Up to date">Up to date</option>
                        </select>
                    </div>

                    <button type="submit" name="add_child"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                        Register Child
                    </button>
                </form>
            </div>
        </div>

        <!-- Children list -->
        <div class="lg:col-span-8 twFadeUp" style="animation-delay: 80ms;">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Registered Children</h3>
                            <p class="mt-1 text-sm text-slate-600">Your saved child profiles.</p>
                        </div>
                        <div class="text-sm text-slate-600">
                            Total: <span class="font-semibold text-slate-900"><?= (int)mysqli_num_rows($childrenResult) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Table without horizontal scrolling -->
                <div class="w-full">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3 sm:px-6">Child</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-6">Birth Date</th>
                                <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Age</th>
                                <th class="px-4 py-3 sm:px-6">Status</th>
                                <th class="px-4 py-3 text-right sm:px-6">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <?php if (mysqli_num_rows($childrenResult) > 0):
                                while ($c = mysqli_fetch_assoc($childrenResult)):
                                    $status = (string)$c['vaccination_status'];
                                    $statusLower = strtolower($status);
                                    $isCompleted = ($statusLower === 'completed');
                            ?>
                                <tr class="transition hover:bg-slate-50">
                                    <!-- Child (mobile shows birth+age under name) -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($c['child_name']) ?></div>

                                        <div class="mt-1 text-xs text-slate-500 md:hidden">
                                            Birth: <?= date('d M Y', strtotime($c['birth_date'])) ?>
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500 lg:hidden">
                                            Age: <?= calc_age_text($c['birth_date']) ?>
                                        </div>
                                    </td>

                                    <!-- Birth date (md+) -->
                                    <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                        <?= date('d M Y', strtotime($c['birth_date'])) ?>
                                    </td>

                                    <!-- Age (lg+) -->
                                    <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                            <?= calc_age_text($c['birth_date']) ?>
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <?php if ($isCompleted): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action (stacks on mobile) -->
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                            <a href="editChild.php?id=<?= (int)$c['child_id'] ?>"
                                               class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-200 transition hover:bg-indigo-50 active:scale-[0.99]"
                                               title="Edit">
                                                <i class="fa fa-edit sm:mr-2"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </a>

                                            <a href="childDetails.php?delete=<?= (int)$c['child_id'] ?>"
                                               class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                               onclick="return confirm('Delete this record?');"
                                               title="Delete">
                                                <i class="fa fa-trash sm:mr-2"></i>
                                                <span class="hidden sm:inline">Delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                                        <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                                <i class="fa fa-child"></i>
                                            </div>
                                            <div class="font-semibold text-slate-900">No child records found.</div>
                                            <div class="text-slate-600">Add one using the form on the left.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                    On mobile, birth date and age appear under the child name to fit the screen.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
