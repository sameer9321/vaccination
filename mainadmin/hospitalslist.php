<?php
$pageTitle = "Hospital List";
include "../base/header.php";
include "../includes/db.php";

/*
  This page is for admin only
  Your header already blocks non logged users
  Extra safety check:
*/
if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "admin") {
    header("Location: ../index.php");
    exit;
}

/* Delete hospital */
if (isset($_GET["delete"])) {
    $delete_id = (int)$_GET["delete"];

    if ($delete_id > 0) {
        $stmt_del = mysqli_prepare($conn, "DELETE FROM hospitals WHERE id = ?");
        if (!$stmt_del) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_del, "i", $delete_id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);

        header("Location: hospitalslist.php?deleted=1");
        exit;
    }
}

/* Fetch hospitals */
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
.cardBox{
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    padding:22px;
    background:#fff;
}
.headRow{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.badgeBlue{
    background:#0d6efd;
    color:#fff;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
}
.msgOk{
    padding:10px 12px;
    border-radius:12px;
    background:#d4edda;
    color:#155724;
    margin-bottom:12px;
}
.tableWrap{ overflow:auto; }
table{ width:100%; border-collapse:collapse; }
th, td{ border:1px solid #eef0f5; padding:10px; text-align:center; vertical-align:middle; }
th{ background:#f5f6fa; }
.btnRed{
    display:inline-block;
    padding:8px 12px;
    border-radius:10px;
    background:#dc3545;
    color:#fff;
    text-decoration:none;
    font-size:13px;
}
.btnGray{
    display:inline-block;
    padding:8px 12px;
    border-radius:10px;
    border:1px solid #0d6efd;
    color:#0d6efd;
    text-decoration:none;
    font-size:13px;
}
.smallNote{ font-size:12px; color:#6c757d; margin:0; }
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
                <span class="badgeBlue">Total: <?php echo (int)$total; ?></span>
                <a class="btnGray" href="addHospital.php">Add Hospital</a>
            </div>
        </div>

        <div class="tableWrap">
            <table>
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
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars((string)($h["hospital_name"] ?? "")); ?></td>
                                <td><?php echo htmlspecialchars((string)($h["address"] ?? "")); ?></td>
                                <td><?php echo htmlspecialchars((string)($h["phone"] ?? "")); ?></td>
                                <td><?php echo htmlspecialchars((string)($h["email"] ?? "")); ?></td>
                                <td>
                                    <a
                                        class="btnRed"
                                        href="hospitalslist.php?delete=<?php echo (int)$h["id"]; ?>"
                                        onclick="return confirm('Delete this hospital?');"
                                    >
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="color:#6c757d;">No hospitals found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<?php include "../base/footer.php"; ?>
