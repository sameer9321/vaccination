<?php
session_start();

$pageTitle = "Vaccination Report";
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

$reports = [];

$stmt = mysqli_prepare($conn, "
    SELECT
        c.child_name,
        b.vaccine_name,
        b.booking_date,
        b.status,
        h.hospital_name
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    LEFT JOIN hospitals h ON h.id = b.hospital_id
    WHERE c.parent_id = ?
      AND (
        LOWER(IFNULL(b.status,'')) IN ('done','completed','vaccinated')
        OR b.booking_date < CURDATE()
      )
    ORDER BY b.booking_date DESC
");

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<!--
  Responsive notes:
  - No sideways scrolling on mobile
  - On mobile: vaccine, hospital, date, status go under child name
  - Desktop keeps full columns
  - Animations: twFadeUp entrance + hover transitions
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
                <span class="text-slate-700">Vaccination Report</span>
            </div>

            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                Vaccination Report
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Previous vaccinations for your children.
            </p>
        </div>

        <div class="flex">
            <a href="parentdashboard.php"
               class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-200 transition hover:bg-indigo-50 active:scale-[0.99]">
                Back
            </a>
        </div>
    </div>

    <!-- Card -->
    <div class="twFadeUp rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 m-0">History</h3>
                    <p class="mt-1 text-sm text-slate-600">Completed and past date records.</p>
                </div>

                <span class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">
                    Total: <?= count($reports) ?>
                </span>
            </div>
        </div>

        <!-- Table (no scroll, hide columns on small screens) -->
        <div class="w-full">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3 sm:px-6">No</th>
                        <th class="px-4 py-3 sm:px-6">Child</th>
                        <th class="hidden sm:table-cell px-4 py-3 sm:px-6">Vaccine</th>
                        <th class="hidden md:table-cell px-4 py-3 sm:px-6">Hospital</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Date</th>
                        <th class="hidden lg:table-cell px-4 py-3 sm:px-6">Status</th>
                        <th class="hidden xl:table-cell px-4 py-3 sm:px-6">Report</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (count($reports) > 0): ?>
                        <?php $i = 1; foreach ($reports as $r): ?>
                            <?php
                                $stRaw = (string)($r["status"] ?? "");
                                $st = strtolower($stRaw);
                                $isOk = in_array($st, ["done","completed","vaccinated"], true);
                                $isPending = ($st === "pending");
                            ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                                <!-- Child (mobile shows all details) -->
                                <td class="px-4 py-4 sm:px-6">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($r["child_name"] ?? "") ?></div>

                                    <div class="mt-1 text-xs text-slate-500 sm:hidden">
                                        Vaccine: <?= htmlspecialchars($r["vaccine_name"] ?? "N/A") ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500 md:hidden">
                                        Hospital: <?= htmlspecialchars($r["hospital_name"] ?? "N/A") ?>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500 lg:hidden">
                                        Date: <?= htmlspecialchars($r["booking_date"] ?? "") ?>
                                    </div>

                                    <div class="mt-2 lg:hidden">
                                        <?php if ($isOk): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                <?= htmlspecialchars($stRaw ?: "Completed") ?>
                                            </span>
                                        <?php elseif ($isPending): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                                <?= htmlspecialchars($stRaw ?: "Pending") ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                <?= htmlspecialchars($stRaw ?: "Unknown") ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-2 text-xs text-slate-500 xl:hidden">
                                        Report: Not available
                                    </div>
                                </td>

                                <!-- Vaccine (sm+) -->
                                <td class="hidden sm:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                        <?= htmlspecialchars($r["vaccine_name"] ?? "N/A") ?>
                                    </span>
                                </td>

                                <!-- Hospital (md+) -->
                                <td class="hidden md:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa fa-hospital-o text-slate-400"></i>
                                        <?= htmlspecialchars($r["hospital_name"] ?? "N/A") ?>
                                    </span>
                                </td>

                                <!-- Date (lg+) -->
                                <td class="hidden lg:table-cell px-4 py-4 text-slate-700 sm:px-6">
                                    <?= htmlspecialchars($r["booking_date"] ?? "") ?>
                                </td>

                                <!-- Status (lg+) -->
                                <td class="hidden lg:table-cell px-4 py-4 sm:px-6">
                                    <?php if ($isOk): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                            <?= htmlspecialchars($stRaw ?: "Completed") ?>
                                        </span>
                                    <?php elseif ($isPending): ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-100">
                                            <?= htmlspecialchars($stRaw ?: "Pending") ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                            <?= htmlspecialchars($stRaw ?: "Unknown") ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Report (xl+) -->
                                <td class="hidden xl:table-cell px-4 py-4 text-slate-600 sm:px-6">
                                    Not available
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-file-text-o"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No previous vaccination records found.</div>
                                    <div class="text-sm text-slate-600">Once vaccinations are completed, they will appear here.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
            On mobile, details appear under the child name to fit the screen.
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
