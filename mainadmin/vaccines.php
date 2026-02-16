<?php
$pageTitle = "Vaccines";
include '../base/header.php';
include '../includes/db.php';

/* Add vaccine (use numeric stock) */
if (isset($_POST['add'])) {
    $name  = trim((string)($_POST['name'] ?? ''));
    $stock = trim((string)($_POST['stock'] ?? ''));

    if ($name !== '' && $stock !== '' && is_numeric($stock)) {
        $stockInt = (int)$stock;

        $stmt = mysqli_prepare($conn, "INSERT INTO vaccines(name, stock) VALUES(?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $name, $stockInt);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: vaccines.php?added=1");
        exit;
    } else {
        header("Location: vaccines.php?error=1");
        exit;
    }
}

/* Delete vaccine */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = mysqli_prepare($conn, "DELETE FROM vaccines WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $deleteId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: vaccines.php?deleted=1");
    exit;
}

/* Fetch vaccines */
$result = mysqli_query($conn, "SELECT * FROM vaccines ORDER BY id DESC");
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<!--
  Responsive + no weird scrolling:
  - Wrap in container-fluid and use internal overflow for table only
  - Use your Tailwind style utilities + twFadeUp animation class (from header.php)
  - Card header stacks on small screens
  - Form becomes 1 column on mobile, 3 columns on md+
-->

<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Inventory
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-flask mr-2 text-emerald-700"></i>
                    Vaccines
                </h2>
                <p class="mt-1 text-sm text-slate-600">Add, track, and remove vaccine types with stock counts.</p>

                <?php if (isset($_GET['added'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <i class="fa fa-check mr-2"></i> Vaccine added successfully
                    </div>
                <?php elseif (isset($_GET['deleted'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                        <i class="fa fa-trash mr-2"></i> Vaccine deleted successfully
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="mt-3 inline-flex items-center rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 ring-1 ring-amber-100">
                        <i class="fa fa-exclamation-triangle mr-2"></i> Please enter a name and a valid stock number
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

    <!-- Add form -->
    <div class="twFadeUp mt-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <form method="post" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
            <div class="md:col-span-6">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Vaccine Name</label>
                <div class="relative">
                    <i class="fa fa-tag pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                        name="name"
                        placeholder="e.g., Polio"
                        required
                    >
                </div>
            </div>

            <div class="md:col-span-4">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Stock</label>
                <div class="relative">
                    <i class="fa fa-cubes pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                        name="stock"
                        placeholder="e.g., 120"
                        inputmode="numeric"
                        required
                    >
                </div>
            </div>

            <div class="md:col-span-2">
                <button
                    class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.99]"
                    name="add"
                    type="submit"
                >
                    <i class="fa fa-plus mr-2"></i>
                    Add
                </button>
            </div>
        </form>
    </div>

    <!-- List table -->
    <div class="twFadeUp mt-5 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="text-sm font-semibold text-slate-900">
                Vaccine List
            </div>

            <div class="w-full sm:w-72">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        id="vaxSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                        placeholder="Search vaccine..."
                    >
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm" id="vaxTable">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:80px;">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Vaccine</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:160px;">Stock</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6" style="width:160px;">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if ($total > 0): ?>
                        <?php $i = 1; while ($v = mysqli_fetch_assoc($result)): ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                                <td class="px-5 py-4 sm:px-6">
                                    <div class="font-semibold text-slate-900">
                                        <?= htmlspecialchars($v['name'] ?? '') ?>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">ID: #<?= (int)$v['id'] ?></div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <?= htmlspecialchars((string)($v['stock'] ?? '0')) ?>
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <a
                                        href="vaccines.php?delete=<?= (int)$v['id'] ?>"
                                        class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                        onclick="return confirm('Delete this vaccine?');"
                                    >
                                        <i class="fa fa-trash mr-2"></i>
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                        <i class="fa fa-flask"></i>
                                    </div>
                                    <div class="font-semibold text-slate-900">No vaccines yet</div>
                                    <div class="text-slate-600">Add your first vaccine using the form above.</div>
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
        // Search filter (no extra scrolling, only hides rows)
        document.getElementById('vaxSearch').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#vaxTable tbody tr');

            rows.forEach(row => {
                // 2nd column is Vaccine name
                let nameCell = row.querySelector('td:nth-child(2)');
                let name = (nameCell ? nameCell.textContent : '').toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'none';
            });
        });
    </script>
</div>

<?php include '../base/footer.php'; ?>
