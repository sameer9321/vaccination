<?php
session_start();

$pageTitle = "Edit Child";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== "parent") {
    header("Location: ../../../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "");

$parentId = (int)($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $userEmail = "";

    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "i", $userId);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        $rowU = $resU ? mysqli_fetch_assoc($resU) : null;
        mysqli_stmt_close($stmtU);

        if ($rowU && isset($rowU["email"])) {
            $userEmail = (string)$rowU["email"];
        }
    }

    if ($userEmail !== "") {
        $stmtP = mysqli_prepare($conn, "SELECT parent_id FROM parents WHERE email = ? LIMIT 1");
        if ($stmtP) {
            mysqli_stmt_bind_param($stmtP, "s", $userEmail);
            mysqli_stmt_execute($stmtP);
            $resP = mysqli_stmt_get_result($stmtP);
            $rowP = $resP ? mysqli_fetch_assoc($resP) : null;
            mysqli_stmt_close($stmtP);

            if ($rowP && isset($rowP["parent_id"])) {
                $parentId = (int)$rowP["parent_id"];
            }
        }

        if ($parentId <= 0) {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO parents (parent_name, email, password) VALUES (?, ?, ?)");
            if ($stmtIns) {
                $blankPass = "";
                mysqli_stmt_bind_param($stmtIns, "sss", $username, $userEmail, $blankPass);
                mysqli_stmt_execute($stmtIns);
                $parentId = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmtIns);
            }
        }
    }

    if ($parentId > 0) {
        $_SESSION["parent_id"] = $parentId;
    }
}

if ($parentId <= 0) {
    die("Parent not linked. Please log out and log in again.");
}

$childId = (int)($_GET["id"] ?? 0);
if ($childId <= 0) {
    header("Location: childDetails.php");
    exit;
}

$child = null;
$stmt = mysqli_prepare($conn, "
    SELECT child_id, child_name, birth_date, vaccination_status
    FROM children
    WHERE child_id = ? AND parent_id = ?
    LIMIT 1
");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "ii", $childId, $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$child = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$child) {
    die("Child not found or you do not have access.");
}

if (isset($_POST["save_child"])) {
    $childName = trim($_POST["child_name"] ?? "");
    $birthDate = trim($_POST["birth_date"] ?? "");
    $vaccStatus = trim($_POST["vaccination_status"] ?? "Pending");

    if ($childName === "" || $birthDate === "") {
        header("Location: editChild.php?id=" . $childId . "&error=1");
        exit;
    }

    $stmtUp = mysqli_prepare($conn, "
        UPDATE children
        SET child_name = ?, birth_date = ?, vaccination_status = ?
        WHERE child_id = ? AND parent_id = ?
    ");
    if (!$stmtUp) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmtUp, "sssii", $childName, $birthDate, $vaccStatus, $childId, $parentId);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    header("Location: childDetails.php?updated=1");
    exit;
}

include "../base/header.php";
?>

<!--
  Responsive notes:
  - Centered card on large screens, full width on mobile
  - Buttons stack on mobile
  Animations:
  - twFadeUp entrance on the page section
  - hover transitions on the card + buttons
-->

<div class="py-4">
    <!-- Top -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="parentdashboard.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                    <i class="fa fa-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="childDetails.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                    <i class="fa fa-child"></i>
                    <span>Children</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700">Edit</span>
            </div>

            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Edit Child</h2>
            <p class="mt-1 text-sm text-slate-600">Update child details and vaccination status.</p>
        </div>

        <div class="flex">
            <a href="childDetails.php"
               class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md active:translate-y-0">
                Back
            </a>
        </div>
    </div>

    <?php if (isset($_GET["error"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-rose-50 p-4 text-rose-800 ring-1 ring-rose-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Missing fields</div>
                    <div class="mt-1 text-sm">Please fill required fields.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="twFadeUp mx-auto max-w-2xl">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Child Information</h3>
                    <p class="mt-1 text-sm text-slate-600">Keep details accurate for scheduling.</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                    <i class="fa fa-edit"></i>
                </div>
            </div>

            <form method="post" class="mt-6 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Child Name</label>
                    <input type="text" name="child_name"
                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                           required value="<?= htmlspecialchars($child["child_name"] ?? "") ?>">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Birth Date</label>
                        <input type="date" name="birth_date"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               required value="<?= htmlspecialchars($child["birth_date"] ?? "") ?>">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Vaccination Status</label>
                        <?php $vs = (string)($child["vaccination_status"] ?? "Pending"); ?>
                        <select name="vaccination_status"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="Pending" <?= $vs === "Pending" ? "selected" : "" ?>>Pending</option>
                            <option value="Up to date" <?= $vs === "Up to date" ? "selected" : "" ?>>Up to date</option>
                            <option value="Completed" <?= $vs === "Completed" ? "selected" : "" ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="submit" name="save_child"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                        Save Changes
                    </button>

                    <a href="childDetails.php"
                       class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                        Cancel
                    </a>

                    <div class="sm:ml-auto text-xs text-slate-500">
                        Child ID: #<?= (int)$childId ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
