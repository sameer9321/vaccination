<?php
session_start();

$pageTitle = "Add Child";
include '../includes/db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'parent') {
    header("Location: ../../../index.php");
    exit;
}

/*
  IMPORTANT (kept your logic, but fixed a common bug):
  You were using user_id as parent_id. In your project, children.parent_id links to parents.parent_id.
  So we resolve parent_id from parents table using the logged in user's email, same approach used in other parent pages.
*/
$userId   = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? 'Parent');
$parentId = (int)($_SESSION['parent_id'] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = mysqli_fetch_assoc($resU);
        mysqli_stmt_close($stmtU);

        $email = (string)($rowU['email'] ?? '');
        if ($email !== '') {
            $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtP, "s", $email);
            mysqli_stmt_execute($stmtP);
            $resP = mysqli_stmt_get_result($stmtP);
            $rowP = mysqli_fetch_assoc($resP);
            mysqli_stmt_close($stmtP);

            if ($rowP && isset($rowP['parent_id'])) {
                $parentId = (int)$rowP['parent_id'];
            } else {
                // auto create parent if missing (same style as other pages)
                $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, '')");
                mysqli_stmt_bind_param($stmtIns, "ss", $username, $email);
                mysqli_stmt_execute($stmtIns);
                $parentId = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmtIns);
            }

            $_SESSION['parent_id'] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please re-login.");
}

$error = "";

if (isset($_POST['add_child'])) {
    $child_name = trim((string)($_POST['child_name'] ?? ''));
    $birth_date = trim((string)($_POST['birth_date'] ?? ''));
    $vaccination_status = trim((string)($_POST['vaccination_status'] ?? 'Pending'));

    if ($child_name === '' || $birth_date === '') {
        $error = "Please fill all required fields.";
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO children (parent_id, child_name, birth_date, vaccination_status)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt) {
            $error = "Server error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "isss", $parentId, $child_name, $birth_date, $vaccination_status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: childDetails.php?added=1");
            exit;
        }
    }
}

include '../base/header.php';
?>

<!--
  UI notes:
  - Full responsive layout, single card centered on large screens
  - Animations:
    - twFadeUp for entrance
    - hover lift and ring glow on card
    - buttons have active scale + hover shadow
  - No extra backend logic changed, only fixed parentId mapping to match the rest of your system
-->

<div class="py-4">
    <!-- Header strip -->
    <div class="twFadeUp mb-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                    Parent portal
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">Add Child</h2>
                <p class="mt-1 text-sm text-slate-600">Create a new child profile for vaccination tracking.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="childDetails.php"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                    <i class="fa fa-arrow-left mr-2"></i>
                    Back to Child Details
                </a>
                <a href="parentdashboard.php"
                   class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.99]">
                    <i class="fa fa-dashboard mr-2"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-4xl">
        <?php if ($error): ?>
            <div class="twFadeUp mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <div class="flex items-start gap-2">
                    <i class="fa fa-exclamation-circle mt-0.5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Child Information</h3>
                    <p class="mt-1 text-sm text-slate-600">Fill the details below. You can edit later if needed.</p>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-slate-50 px-3 py-1 ring-1 ring-slate-200">Parent ID: #<?= (int)$parentId ?></span>
                </div>
            </div>

            <div class="mt-5">
                <form method="post" class="grid grid-cols-1 gap-4 md:grid-cols-12">
                    <div class="md:col-span-5">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Child Name</label>
                        <div class="relative">
                            <i class="fa fa-user-o pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                name="child_name"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                                placeholder="e.g. Ayaan Khan"
                            >
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Birth Date</label>
                        <div class="relative">
                            <i class="fa fa-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="date"
                                name="birth_date"
                                required
                                max="<?= date('Y-m-d') ?>"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Vaccination Status</label>
                        <div class="relative">
                            <i class="fa fa-shield pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <select
                                name="vaccination_status"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                                <option value="Pending">Pending</option>
                                <option value="Up to date">Up to date</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <i class="fa fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                    </div>

                    <div class="md:col-span-12">
                        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">
                                Tip: Use “Pending” if you are adding the child for the first time.
                            </p>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <a href="childDetails.php"
                                   class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    name="add_child"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow active:scale-[0.99]"
                                >
                                    <i class="fa fa-check mr-2"></i>
                                    Save Child
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-violet-700 ring-1 ring-slate-200">
                        <i class="fa fa-info"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-900">Where will this show?</div>
                        <div class="mt-1 text-sm text-slate-600">
                            The child will appear in Child Details, and can be selected while booking a hospital appointment.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../base/footer.php'; ?>
