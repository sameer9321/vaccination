<?php
$pageTitle = "Hospital List";
include "../base/header.php";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "admin") {
    header("Location: ../index.php");
    exit;
}

/* =========================
   DELETE
========================= */
if (isset($_GET["delete"])) {
    $delete_id = (int)($_GET["delete"] ?? 0);

    if ($delete_id > 0) {
        $stmt_del = mysqli_prepare($conn, "DELETE FROM hospitals WHERE id = ?");
        if (!$stmt_del) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_del, "i", $delete_id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);
    }

    header("Location: hospitalslist.php?deleted=1");
    exit;
}

/* =========================
   FETCH
========================= */
$rows = [];
$result = mysqli_query($conn, "SELECT id, hospital_name, address, phone, email FROM hospitals ORDER BY id DESC");
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}
$total = count($rows);
?>

<style>
/* page shell */
.cardBox{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.06);
    padding:18px;
    background:#fff;
}
.headRow{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
}
.badgeBlue{
    background:#0d6efd;
    color:#fff;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}
.msgOk{
    padding:10px 12px;
    border-radius:12px;
    background:#d4edda;
    color:#155724;
    margin-bottom:12px;
}
.btnRed{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:10px;
    background:#dc3545;
    color:#fff;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    white-space:nowrap;
}
.btnGray{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:10px;
    border:1px solid #0d6efd;
    color:#0d6efd;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    white-space:nowrap;
}
.smallNote{ font-size:12px; color:#6c757d; margin:0; }

/* toolbar */
.searchWrap{ width: 260px; max-width: 100%; }
.searchInput{
    width:100%;
    padding:10px 12px;
    border-radius:12px;
    border:1px solid #e6e8ef;
    outline:none;
}
.searchInput:focus{
    border-color:#b6d4fe;
    box-shadow:0 0 0 4px rgba(13,110,253,0.10);
}

/* table */
.tableWrap{ overflow:auto; }
.tableX{ width:100%; border-collapse:collapse; min-width: 980px; }
.tableX th, .tableX td{
    border:1px solid #eef0f5;
    padding:10px;
    text-align:left;
    vertical-align:middle;
}
.tableX th{
    background:#f5f6fa;
    text-transform:uppercase;
    font-size:11px;
    letter-spacing:0.8px;
    color:#444;
}
.tableX td:nth-child(1),
.tableX th:nth-child(1),
.tableX td:last-child,
.tableX th:last-child{
    text-align:center;
}

/* mobile: remove horizontal scrolling by stacking rows */
@media (max-width: 768px){
    .tableWrap{ overflow: visible; }       /* no scrolling */
    .tableX{ min-width: 0; border:0; }
    .tableX thead{ display:none; }

    .tableX tr{
        display:block;
        margin-bottom:12px;
        border:1px solid #eef0f5;
        border-radius:14px;
        overflow:hidden;
        background:#fff;
    }

    .tableX td{
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:10px 12px;
        border:0;
        border-bottom:1px solid #f1f3f8;
        text-align:left !important;
    }

    .tableX td:last-child{
        border-bottom:0;
    }

    .tableX td::before{
        content: attr(data-label);
        font-weight:700;
        color:#6c757d;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:0.6px;
        flex: 0 0 42%;
    }

    .btnRed, .btnGray{
        width:100%;
    }

    .actionsCell{
        flex-direction:column;
        align-items:stretch;
    }
}
</style>

<div class="container-fluid">

    <?php if (isset($_GET["deleted"])): ?>
        <div class="msgOk">Hospital deleted successfully.</div>
    <?php endif; ?>

    <div class="cardBox">
        <div class="headRow">
            <div>
                <h3 style="margin:0;">Hospital List</h3>
                <p class="smallNote">All hospitals added in the system</p>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div class="searchWrap">
                    <input id="hospitalSearch" class="searchInput" type="text" placeholder="Search hospital or email...">
                </div>
                <span class="badgeBlue">Total: <?= (int)$total ?></span>
                <a class="btnGray" href="addHospital.php">Add Hospital</a>
            </div>
        </div>

        <div class="tableWrap">
            <table class="tableX" id="hospitalTable">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Hospital</th>
                        <th>Address</th>
                        <th style="width:160px;">Phone</th>
                        <th>Email</th>
                        <th style="width:140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php $i = 1; foreach ($rows as $h): ?>
                            <tr>
                                <td data-label="No"><?= $i++ ?></td>
                                <td data-label="Hospital"><?= htmlspecialchars((string)($h["hospital_name"] ?? "")) ?></td>
                                <td data-label="Address"><?= htmlspecialchars((string)($h["address"] ?? "")) ?></td>
                                <td data-label="Phone"><?= htmlspecialchars((string)($h["phone"] ?? "")) ?></td>
                                <td data-label="Email"><?= htmlspecialchars((string)($h["email"] ?? "")) ?></td>
                                <td data-label="Action" class="actionsCell">
                                    <a
                                        class="btnRed"
                                        href="hospitalslist.php?delete=<?= (int)$h["id"] ?>"
                                        onclick="return confirm('Delete this hospital?');"
                                    >
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="color:#6c757d; text-align:center;">No hospitals found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="smallNote" style="margin-top:12px;">
            On mobile, rows turn into cards, so you will not get sideways scrolling.
        </p>
    </div>

</div>

<script>
(function(){
    const input = document.getElementById('hospitalSearch');
    const table = document.getElementById('hospitalTable');
    if (!input || !table) return;

    input.addEventListener('keyup', function(){
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(tr => {
            const txt = tr.textContent.toLowerCase();
            tr.style.display = txt.includes(filter) ? '' : 'none';
        });
    });
})();
</script>

<?php include "../base/footer.php"; ?>
