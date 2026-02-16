<?php 
$pageTitle = "All Children"; 
include '../base/header.php'; 
include '../includes/db.php';  

/* =========================
   DELETE CHILD
========================= */
if (isset($_GET['delete'])) {

    $deleteId = (int) $_GET['delete'];

    mysqli_begin_transaction($conn);

    try {

        mysqli_query($conn, "DELETE FROM bookings WHERE child_id = $deleteId");

        $stmt = mysqli_prepare($conn, "DELETE FROM children WHERE child_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $deleteId);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        header("Location: children.php?deleted=1");

    } catch (Exception $e) {

        mysqli_rollback($conn);
        header("Location: children.php?error=1");
    }

    exit;
}


/* =========================
   UPDATE CHILD
========================= */
if (isset($_POST['update_child'])) {

    $child_id = (int) $_POST['child_id'];
    $child_name = mysqli_real_escape_string($conn, $_POST['child_name']);
    $birth_date = $_POST['birth_date'];
    $vaccination_status = mysqli_real_escape_string($conn, $_POST['vaccination_status']);

    // SERVER SIDE DATE VALIDATION (Future date not allowed)
    if ($birth_date > date('Y-m-d')) {
        header("Location: children.php?error=future_date");
        exit;
    }

    $stmt = mysqli_prepare($conn, "
        UPDATE children 
        SET child_name = ?, birth_date = ?, vaccination_status = ?
        WHERE child_id = ?
    ");

    mysqli_stmt_bind_param($stmt, "sssi", $child_name, $birth_date, $vaccination_status, $child_id);
    mysqli_stmt_execute($stmt);

    header("Location: children.php?updated=1");
    exit;
}


/* =========================
   FETCH DATA
========================= */
$result = mysqli_query($conn, "
    SELECT 
        c.child_id,
        c.child_name,
        c.birth_date,
        c.vaccination_status,
        p.parent_name
    FROM children c
    LEFT JOIN parents p ON p.parent_id = c.parent_id
    ORDER BY c.child_id DESC
");
?>

<!--
  UI notes:
  - Fully responsive table: converts to horizontal scroll only on small screens
  - Modern Tailwind look while keeping your PHP/JS logic unchanged
  - Animations:
    - twFadeUp entrance (from your Tailwind header setup)
    - hover lift on cards, hover shade on rows, active scale on buttons
  - Modal: kept Bootstrap modal behavior, but styled inner content with Tailwind classes
-->

<div class="py-4">
    <div class="twFadeUp rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Admin module
                </div>

                <h2 class="mt-3 text-lg font-semibold text-slate-900 sm:text-xl">
                    <i class="fa fa-child mr-2 text-sky-600"></i>
                    Child Management
                </h2>
                <p class="mt-1 text-sm text-slate-600">View, search, edit, and delete child records.</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <?php if (isset($_GET['deleted'])): ?>
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                            <i class="fa fa-check mr-2"></i>Record removed successfully
                        </span>
                    <?php endif; ?>

                    <?php if (isset($_GET['updated'])): ?>
                        <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                            <i class="fa fa-refresh mr-2"></i>Record updated successfully
                        </span>
                    <?php endif; ?>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'future_date'): ?>
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
                            <i class="fa fa-exclamation-triangle mr-2"></i>Future date is not allowed.
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[360px]">
                <div class="flex items-center gap-2">
                    <div class="relative w-full">
                        <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="text"
                            id="childSearch"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                            placeholder="Search by name..."
                        >
                    </div>

                    <span class="inline-flex shrink-0 items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                        Total: <?= mysqli_num_rows($result); ?>
                    </span>
                </div>

                <div class="text-xs text-slate-500">
                    Tip: On small screens, scroll sideways to see all columns.
                </div>
            </div>
        </div>
    </div>

    <div class="twFadeUp mt-5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm" id="childrenTable">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">#</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Child Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Date of Birth</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Parent Name</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6">Status</th>
                        <th class="whitespace-nowrap px-5 py-3 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                <?php
                $i = 1;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):

                        $status = strtolower(trim($row['vaccination_status'] ?? 'pending'));

                        $badgeClass = ($status === 'pending')
                            ? 'bg-amber-50 text-amber-700 ring-amber-100'
                            : (in_array($status, ['done','completed','vaccinated'])
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                : 'bg-slate-100 text-slate-700 ring-slate-200'
                              );
                ?>
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6"><?= $i++ ?></td>

                        <td class="px-5 py-4 sm:px-6">
                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['child_name']) ?></div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:px-6">
                            <?= date('M d, Y', strtotime($row['birth_date'])) ?>
                        </td>

                        <td class="px-5 py-4 text-slate-700 sm:px-6">
                            <?= htmlspecialchars($row['parent_name'] ?? 'Guest') ?>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 <?= $badgeClass ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                            <div class="flex justify-end gap-2">
                                <!-- EDIT BUTTON -->
                                <button
                                    class="editBtn inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-200 transition hover:bg-sky-50 active:scale-[0.99]"
                                    data-id="<?= $row['child_id'] ?>"
                                    data-name="<?= htmlspecialchars($row['child_name']) ?>"
                                    data-birth="<?= $row['birth_date'] ?>"
                                    data-status="<?= $status ?>"
                                    type="button"
                                    title="Edit"
                                >
                                    <i class="fa fa-edit"></i>
                                    <span class="hidden sm:inline">Edit</span>
                                </button>

                                <!-- DELETE BUTTON -->
                                <a
                                   href="children.php?delete=<?= $row['child_id'] ?>"
                                   class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-[0.99]"
                                   onclick="return confirm('Deleting this child will also remove their booking history. Continue?');"
                                   title="Delete"
                                >
                                    <i class="fa fa-trash"></i>
                                    <span class="hidden sm:inline">Delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>

                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-600 sm:px-6">
                            <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                    <i class="fa fa-folder-open-o"></i>
                                </div>
                                <div class="font-semibold text-slate-900">No child records found in the system.</div>
                                <div class="text-slate-600">When records exist, they will appear here.</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- =========================
     EDIT MODAL (Bootstrap stays, Tailwind styled inside)
========================= -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content overflow-hidden rounded-2xl border-0 shadow-lg">

      <div class="modal-header border-0 bg-slate-50 px-4 py-3">
        <h5 class="modal-title text-sm font-semibold text-slate-900">Edit Child</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4 py-4">
        <input type="hidden" name="child_id" id="edit_id">

        <div class="mb-3">
            <label class="mb-1 block text-sm font-semibold text-slate-700">Child Name</label>
            <div class="relative">
                <i class="fa fa-user-o pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="text"
                    name="child_name"
                    id="edit_name"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                    required
                >
            </div>
        </div>

        <div class="mb-3">
            <label class="mb-1 block text-sm font-semibold text-slate-700">Birth Date</label>
            <div class="relative">
                <i class="fa fa-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input
                   type="date"
                   name="birth_date"
                   id="edit_birth"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                   max="<?= date('Y-m-d'); ?>"
                   required
                >
            </div>
        </div>

        <div class="mb-1">
            <label class="mb-1 block text-sm font-semibold text-slate-700">Vaccination Status</label>
            <div class="relative">
                <i class="fa fa-shield pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <select
                    name="vaccination_status"
                    id="edit_status"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                >
                    <option value="pending">Pending</option>
                    <option value="done">Done</option>
                    <option value="completed">Completed</option>
                </select>
                <i class="fa fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>
      </div>

      <div class="modal-footer border-0 bg-white px-4 py-3">
        <button type="submit" name="update_child"
                class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 active:scale-[0.99]">
            <i class="fa fa-check mr-2"></i>
            Update
        </button>
        <button type="button"
                class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]"
                data-bs-dismiss="modal">
            Cancel
        </button>
      </div>

    </form>
  </div>
</div>


<script>
/* SEARCH FUNCTION (same logic, just uses the same table) */
document.getElementById('childSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#childrenTable tbody tr');

    rows.forEach(row => {
        let nameCell = row.cells[1];
        if (!nameCell) return;
        let name = nameCell.textContent.toLowerCase();
        row.style.display = name.includes(filter) ? '' : 'none';
    });
});


/* EDIT MODAL (same behavior, Bootstrap modal kept) */
document.querySelectorAll('.editBtn').forEach(button => {

    button.addEventListener('click', function() {

        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_birth').value = this.dataset.birth;
        document.getElementById('edit_status').value = this.dataset.status;

        let modal = new bootstrap.Modal(document.getElementById('editModal'));
        modal.show();
    });

});
</script>

<?php include '../base/footer.php'; ?>
