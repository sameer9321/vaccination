<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "My Profile";
include "../includes/db.php";

if (!isset($_SESSION["role"]) || strtolower((string)$_SESSION["role"]) !== "parent") {
    header("Location: ../index.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$parentId = (int)($_SESSION["parent_id"] ?? 0);

if ($parentId <= 0 && $userId > 0) {
    $stmtU = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtU, "i", $userId);
    mysqli_stmt_execute($stmtU);
    $resU = mysqli_stmt_get_result($stmtU);
    $rowU = mysqli_fetch_assoc($resU);
    
    if ($rowU) {
        $userEmail = $rowU["email"];
        $stmtP = mysqli_prepare($conn, "SELECT parent_id, parent_name FROM parents WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtP, "s", $userEmail);
        mysqli_stmt_execute($stmtP);
        $resP = mysqli_stmt_get_result($stmtP);
        $rowP = mysqli_fetch_assoc($resP);
        if ($rowP) {
            $parentId = (int)$rowP["parent_id"];
            $_SESSION["parent_id"] = $parentId;
        }
    }
}

if ($parentId <= 0) {
    die("Parent profile not linked. Please re-login.");
}

if (isset($_POST["save_profile"])) {
    $parentName = trim((string)$_POST["parent_name"]);
    $address = trim((string)$_POST["address"]);
    $phone = trim((string)$_POST["phone"]);

    if ($parentName !== "" && $address !== "" && $phone !== "") {
        $stmtUp = mysqli_prepare($conn, "UPDATE parents SET parent_name = ?, address = ?, phone = ? WHERE parent_id = ?");
        mysqli_stmt_bind_param($stmtUp, "sssi", $parentName, $address, $phone, $parentId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        
        header("Location: profile.php?saved=1");
        exit;
    } else {
        header("Location: profile.php?error=1");
        exit;
    }
}

$stmt = mysqli_prepare($conn, "SELECT parent_name, address, phone, email FROM parents WHERE parent_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$parent = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

include "../base/header.php";
?>

<!--
  Responsive notes:
  - Two column layout becomes single column on mobile
  - Buttons stack on small screens
  Animations:
  - twFadeUp on cards (entrance)
  - hover transitions on cards and buttons
-->

<div class="py-4">
    <!-- Top bar -->
    <div class="twFadeUp mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="parentdashboard.php" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-white hover:text-slate-700">
                    <i class="fa fa-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700">Profile Settings</span>
            </div>
            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">My Profile</h2>
            <p class="mt-1 text-sm text-slate-600">Update your contact details and profile picture.</p>
        </div>

        <div class="flex">
            <span class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-200">
                Parent ID: #<?= $parentId ?>
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET["saved"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i class="fa fa-check"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Saved</div>
                    <div class="mt-1 text-sm">Profile updated successfully.</div>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET["error"])): ?>
        <div class="twFadeUp mb-4 rounded-2xl bg-rose-50 p-4 text-rose-800 ring-1 ring-rose-100">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold">Missing fields</div>
                    <div class="mt-1 text-sm">Please fill all required fields.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Profile picture -->
        <div class="lg:col-span-4 twFadeUp">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Profile Picture</h3>
                        <p class="mt-1 text-sm text-slate-600">Upload a new photo.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <i class="fa fa-user-circle"></i>
                    </div>
                </div>

                <?php 
                    $picQ = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = '$userId'");
                    $picR = mysqli_fetch_assoc($picQ);
                    $userPic = (!empty($picR['profile_pic'])) ? $picR['profile_pic'] : 'user.png';
                ?>

                <div class="mt-5 flex flex-col items-center">
                    <div class="h-36 w-36 overflow-hidden rounded-2xl bg-slate-50 shadow-sm ring-1 ring-slate-200">
                        <img src="../assets/images/<?= $userPic ?>" class="h-full w-full object-cover" alt="Profile">
                    </div>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-5 w-full space-y-3">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Choose Image</label>
                            <input type="file" name="profile_image"
                                   class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                   required>
                            <p class="mt-2 text-xs text-slate-500">JPG or PNG recommended.</p>
                        </div>

                        <button type="submit" name="upload_pic"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                            Change Photo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-8 twFadeUp" style="animation-delay: 80ms;">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Personal Details</h3>
                        <p class="mt-1 text-sm text-slate-600">Update your contact information.</p>
                    </div>
                    <div class="hidden sm:flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-50 text-slate-700 ring-1 ring-slate-200">
                        <i class="fa fa-id-card-o"></i>
                    </div>
                </div>

                <form method="post" class="mt-6 space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Full Name</label>
                            <input type="text" name="parent_name"
                                   class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                   value="<?= htmlspecialchars($parent["parent_name"] ?? '') ?>" required>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Email Address</label>
                            <input type="email"
                                   class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 shadow-sm"
                                   value="<?= htmlspecialchars($parent["email"] ?? '') ?>" disabled>
                            <p class="mt-2 text-xs text-slate-500">Email is read only.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="text-sm font-semibold text-slate-700">Residential Address</label>
                            <input type="text" name="address"
                                   class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                   value="<?= htmlspecialchars($parent["address"] ?? '') ?>" required>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Contact Number</label>
                            <input type="text" name="phone"
                                   class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                   value="<?= htmlspecialchars($parent["phone"] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button type="submit" name="save_profile"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.99]">
                            <i class="fa fa-save mr-2"></i>
                            Save Changes
                        </button>

                        <a href="parentdashboard.php"
                           class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.99]">
                            Cancel
                        </a>

                        <div class="sm:ml-auto text-xs text-slate-500">
                            Tip: Keep your phone number updated for reminders.
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../base/footer.php"; ?>
