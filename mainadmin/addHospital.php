<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Hospitals";
include '../base/header.php';
include '../includes/db.php'; // ✅ FIX: missing semicolon in your code

/* =========================
   Delete Hospital
========================= */
if (isset($_GET['delete'])) {
    $deleteId = (int)($_GET['delete'] ?? 0);

    if ($deleteId > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM hospitals WHERE id = ?");
        if (!$stmt) {
            die("Prepare Failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $deleteId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: addHospital.php?deleted=1");
    exit;
}

/* =========================
   Add Hospital
========================= */
if (isset($_POST['add'])) {
    $hospital_name = trim((string)($_POST['hospital_name'] ?? ''));
    $address       = trim((string)($_POST['address'] ?? ''));
    $phone         = trim((string)($_POST['phone'] ?? ''));

    if ($hospital_name !== '' && $address !== '' && $phone !== '') {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO hospitals (hospital_name, address, phone) VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            die("Prepare Failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "sss", $hospital_name, $address, $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: addHospital.php?added=1");
        exit;

    } else {
        header("Location: addHospital.php?error=1");
        exit;
    }
}

/* =========================
   Fetch Hospitals
========================= */
$result = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY id DESC");
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<!-- Tailwind style layout (same style you used in parentdashboard) -->
<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Directory
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-hospital-o mr-2 text-sky-700"></i>
                    Hospitals
                </h2>
                <p class="mt-1 text-sm text-slate-600">Add hospitals and manage the list.</p>

                <?php if (isset($_GET['added'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <i class="fa fa-check mr-2"></i> Hospital added successfully
                    </div>
                <?php elseif (isset($_GET['deleted'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <i class="fa fa-trash mr-2"></i> Hospital deleted successfully
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 ring-1 ring-amber-100">
                        <i class="fa fa-exclamation-triangle mr-2"></i> Please fill all fields
                    </div>
                <?php endif; ?>
            </div>

            <div class="self-start sm:self-center">
                <span class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                    Total: <?= (int)$total ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Add Hospital form -->
    <div class="twFadeUp mt-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <form method="post" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Hospital Name</label>
                <div class="relative">
                    <i class="fa fa-building pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                        name="hospital_name"
                        placeholder="e.g., City Hospital"
                        required
                    >
                </div>
            </div>

            <div class="lg:col-span-5">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Address</label>
                <div class="relative">
                    <i class="fa fa-map-marker pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                        name="address"
                        placeholder="Street, City"
                        required
                    >
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                <div class="relative">
                    <i class="fa fa-phone pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                        name="phone"
                        placeholder="03XXXXXXXXX"
                        required
                    >
                </div>
            </div>

            <div class="lg:col-span-12">
                <button
                    class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]"
                    name="add"
                    type="submit"
                >
                    <i class="fa fa-plus mr-2"></i>
                    Add Hospital
                </button>
            </div>
        </form>
    </div>

    <!-- Hospitals table -->
    <div class="twFadeUp mt-5 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="text-sm font-semibold text-slate-900">Hospital List</div>

            <div class="w-full sm:w-72">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        id="hospitalSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                        placeholder="Search hospital..."
                    >
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm" id="hospitalTable">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:80px;">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Hospital</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Address</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:170px;">Phone</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:160px;">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if ($total > 0): ?>
                        <?php $i = 1; while ($h = mysqli_fetch_assoc($result)): ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                                <td class="px-5 py-4 sm:px-6">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($h['hospital_name'] ?? '') ?></div>
                                    <div class="mt-1 text-xs text-slate-500">ID: #<?= (int)$h['id'] ?></div>
                                </td>

                                <td class="px-5 py-4 text-slate-700 sm:px-6">
                                    <?= htmlspecialchars($h['address'] ?? '') ?>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                                    <?= htmlspecialchars($h['phone'] ?? '') ?>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <a
                                        href="addHospital.php?delete=<?= (int)$h['id'] ?>"
                                        class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                        onclick="return confirm('Are you sure you want to delete this hospital?');"
                                    >
                                        <i class="fa fa-trash mr-2"></i>
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-hospital-o"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No hospitals yet</div>
                                    <div class="text-slate-600">Add your first hospital using the form above.</div>
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

    <script>
        // Search hospitals
        document.getElementById('hospitalSearch').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#hospitalTable tbody tr');

            rows.forEach(row => {
                let nameCell = row.querySelector('td:nth-child(2)');
                let txt = (nameCell ? nameCell.textContent : '').toLowerCase();
                row.style.display = txt.includes(filter) ? '' : 'none';
            });
        });
    </script>
</div>

<?php include '../base/footer.php'; ?>
