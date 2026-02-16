<?php
$pageTitle = "Bookings";
include '../base/header.php';
include '../includes/db.php';

$result = mysqli_query($conn, "
    SELECT 
        c.child_name,
        h.hospital_name AS hospital,
        b.vaccine_name,
        b.booking_date,
        b.status
    FROM bookings b
    JOIN children c ON c.child_id = b.child_id
    JOIN hospitals h ON h.id = b.hospital_id
    ORDER BY b.booking_date DESC
");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
$total = mysqli_num_rows($result);
?>

<style>
.booking-card{
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.06);
    padding:18px;
    background:#fff;
}
.table th{ background:#f5f6fa; }
.status-pill{
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}
.status-pending{ background:#fff3cd; color:#856404; }
.status-done{ background:#d4edda; color:#155724; }
.status-other{ background:#e2e3e5; color:#383d41; }

.searchWrap{ width:260px; max-width:100%; }
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

.tableWrap{ overflow:auto; }
.tableX{ width:100%; border-collapse:collapse; min-width: 980px; }
.tableX th, .tableX td{
    border:1px solid #eef0f5;
    padding:10px;
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
.tableX th:nth-child(1){
    text-align:center;
    width:70px;
}

/* Mobile: no horizontal scroll, convert row to card */
@media (max-width: 768px){
    .tableWrap{ overflow: visible; }     /* remove scrolling */
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

    .tableX td:last-child{ border-bottom:0; }

    .tableX td::before{
        content: attr(data-label);
        font-weight:700;
        color:#6c757d;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:0.6px;
        flex: 0 0 42%;
    }

    .status-pill{ text-align:center; }
}
</style>

<div class="card booking-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:10px;">
        <div>
            <h4 class="m-0">Bookings</h4>
            <small class="text-muted">All bookings in the system</small>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
            <div class="searchWrap">
                <input id="bookingSearch" class="searchInput" type="text" placeholder="Search child, hospital, vaccine...">
            </div>
            <span class="badge bg-primary">Total: <?= (int)$total ?></span>
        </div>
    </div>

    <div class="tableWrap">
        <table class="tableX" id="bookingTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Child</th>
                    <th>Hospital</th>
                    <th>Vaccine</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($total > 0): ?>
                    <?php $i=1; while ($b = mysqli_fetch_assoc($result)):
                        $status = strtolower((string)($b['status'] ?? ''));
                        $badge = 'status-other';
                        if ($status === 'pending') $badge = 'status-pending';
                        if (in_array($status, ['done','completed','vaccinated'], true)) $badge = 'status-done';
                    ?>
                        <tr>
                            <td data-label="No"><?= $i++ ?></td>
                            <td data-label="Child"><?= htmlspecialchars((string)$b['child_name']) ?></td>
                            <td data-label="Hospital"><?= htmlspecialchars((string)$b['hospital']) ?></td>
                            <td data-label="Vaccine"><?= htmlspecialchars((string)$b['vaccine_name']) ?></td>
                            <td data-label="Date"><?= htmlspecialchars((string)$b['booking_date']) ?></td>
                            <td data-label="Status">
                                <span class="status-pill <?= $badge ?>">
                                    <?= htmlspecialchars((string)$b['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-muted" style="text-align:center; padding:18px;">
                            No bookings found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    const input = document.getElementById('bookingSearch');
    const table = document.getElementById('bookingTable');
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

<?php include '../base/footer.php'; ?>
