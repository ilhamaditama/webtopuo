<?php
require_once '../config/app.php';
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ============================
   USER DATA
============================ */
$stmt = $conn->prepare("
    SELECT id, name, email, phone, role, balance 
    FROM users 
    WHERE id = ? LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die('User not found.');
}

$role    = $user['role'];
$balance = (float)$user['balance'];

/* ============================
   TRANSAKSI STATUS COUNT
============================ */

function statusCount(mysqli $conn, int $uid, string $status): int {
    $q = $conn->prepare("SELECT COUNT(*) AS c FROM transactions WHERE user_id = ? AND status = ?");
    $q->bind_param('is', $uid, $status);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    return (int)($row['c'] ?? 0);
}

$pending    = statusCount($conn, $user_id, 'pending');
$processing = statusCount($conn, $user_id, 'processing');
$success    = statusCount($conn, $user_id, 'success');
$failed     = statusCount($conn, $user_id, 'failed');

/* ============================
   HISTORY 10 TERBARU
============================ */
$historyStmt = $conn->prepare("
    SELECT t.*, p.product_name 
    FROM transactions t
    LEFT JOIN products p ON p.id = t.product_id
    WHERE t.user_id = ?
    ORDER BY t.id DESC
    LIMIT 10
");
$historyStmt->bind_param('i', $user_id);
$historyStmt->execute();
$historyRows = $historyStmt->get_result();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MinzzShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg:#020617;
            --card:#0b1120;
            --border:#1e293b;
            --text:#ffffff;
            --muted:#94a3b8;
            --gold:#b89b00;
            --blue:#2563eb;
            --green:#16a34a;
            --red:#b91c1c;
        }
        *{box-sizing:border-box;}
        body{
            margin:0;
            font-family:'Poppins',sans-serif;
            background:var(--bg);
            color:var(--text);
        }
        a{color:inherit;text-decoration:none;}

        .main-container{
            max-width:1100px;
            margin:0 auto;
            padding:14px;
        }

        /* PROFILE CARD */
        .profile-card{
            margin-top:12px;
            background:var(--card);
            border-radius:20px;
            border:1px solid var(--border);
            padding:16px;
            display:flex;
            align-items:center;
            gap:14px;
            box-shadow:0 14px 36px rgba(0,0,0,0.6);
        }
        .avatar{
            width:60px;
            height:60px;
            border-radius:50%;
            background:#1e293b;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:26px;
        }
        .profile-info{
            flex:1;
        }
        .profile-name{
            font-size:18px;
            font-weight:600;
        }
        .role-badge{
            display:inline-block;
            margin-top:4px;
            padding:4px 10px;
            border-radius:999px;
            font-size:11px;
            text-transform:capitalize;
        }
        .role-public{background:#1e293b;color:#7dd3fc;}
        .role-reseller{background:#1e3a8a;color:#38bdf8;}
        .role-admin{background:#450a0a;color:#f87171;}

        .profile-settings{
            cursor:pointer;
            padding:6px;
            border-radius:999px;
            transition:background .12s ease-out;
        }
        .profile-settings:hover{
            background:rgba(148,163,184,0.18);
        }
        .profile-settings svg{
            width:20px;
            height:20px;
        }

        /* CASH CARD */
        .cash-card{
            margin-top:16px;
            background:var(--card);
            border-radius:20px;
            border:1px solid var(--border);
            padding:16px;
        }
        .cash-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
        }
        .cash-left{
            display:flex;
            align-items:center;
            gap:10px;
        }
        .cash-left img{
            width:26px;
            height:26px;
        }
        .cash-title{
            font-size:14px;
            font-weight:600;
        }
        .cash-btns button{
            border:none;
            border-radius:10px;
            padding:7px 16px;
            font-size:13px;
            cursor:pointer;
            font-weight:500;
        }
        .btn-topup{background:#f97316;color:#fff;}
        .btn-redeem{background:#000;color:#fff;margin-left:6px;}
        .cash-amount{
            margin-top:10px;
            font-size:32px;
            font-weight:700;
        }

        /* STATUS CARDS */
        .status-grid{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:12px;
            margin-top:22px;
        }
        @media(min-width:640px){
            .status-grid{grid-template-columns:repeat(4,1fr);}
        }
        .status-card{
            padding:18px 10px;
            border-radius:18px;
            text-align:center;
            font-size:14px;
            font-weight:500;
            color:#fff;
        }
        .status-number{
            font-size:32px;
            font-weight:700;
            margin-bottom:6px;
        }
        .status-pending{background:var(--gold);}
        .status-processing{background:var(--blue);}
        .status-success{background:var(--green);}
        .status-failed{background:var(--red);}

        /* HISTORY */
        .history-title{
            margin-top:28px;
            font-size:18px;
            font-weight:600;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            font-size:13px;
        }
        th, td{
            padding:9px 6px;
            border-bottom:1px solid var(--border);
        }
        th{
            color:var(--muted);
            font-weight:500;
        }
        .empty-box{
            padding:40px 10px;
            text-align:center;
            color:var(--muted);
        }
        .empty-box img{
            width:55px;
            opacity:.7;
            margin-bottom:8px;
        }

        /* MODAL EDIT PROFILE */
        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.7);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:200;
        }
        .modal-backdrop.active{
            display:flex;
        }
        .modal-box{
            width:90%;
            max-width:420px;
            background:#020617;
            border-radius:18px;
            border:1px solid var(--border);
            padding:16px 16px 18px;
            box-shadow:0 20px 50px rgba(0,0,0,0.9);
        }
        .modal-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:10px;
        }
        .modal-title{
            font-size:16px;
            font-weight:600;
        }
        .modal-close{
            cursor:pointer;
            padding:4px 8px;
            border-radius:999px;
        }
        .modal-close:hover{
            background:rgba(148,163,184,0.18);
        }
        .form-group{
            margin-bottom:10px;
        }
        .form-label{
            font-size:12px;
            margin-bottom:4px;
            color:var(--muted);
        }
        .form-control{
            width:100%;
            border-radius:10px;
            border:1px solid #1e293b;
            padding:8px 10px;
            font-size:13px;
            background:#020617;
            color:var(--text);
        }
        .form-submit{
            margin-top:6px;
            width:100%;
            border:none;
            border-radius:999px;
            padding:9px 12px;
            font-size:14px;
            font-weight:500;
            cursor:pointer;
            background:var(--blue);
            color:#fff;
        }
        
        .new-topup {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff !important;
    padding: 10px 20px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.55);
    transition: 0.18s ease-out;
}

.new-topup:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(96, 165, 250, 0.70);
}

.new-topup:active {
    transform: scale(0.96);
}
    </style>
</head>
<body>

<?php include '../include/header.php'; ?>
<?php include '../include/sidebar.php'; ?>

<div class="main-container">

    <!-- PROFILE CARD -->
    <div class="profile-card">
        <div class="avatar">
            <?php echo strtoupper(substr($user['name'],0,1)); ?>
        </div>
        <div class="profile-info">
            <div class="profile-name">
                <?php echo htmlspecialchars($user['name']); ?>
            </div>
            <div class="role-badge role-<?php echo htmlspecialchars($role); ?>">
                <?php echo htmlspecialchars($role); ?>
            </div>
        </div>

        <!-- SETTINGS ICON (SVG) -->
        <div class="profile-settings" id="profileSettingsBtn" title="Edit profil">
            <svg viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.33 1.82l.02.07a2 2 0 1 1-3.38 0l.02-.07A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 2.6 9 1.65 1.65 0 0 0 2 8.4 1.65 1.65 0 0 0 .18 8.07l-.02-.07a2 2 0 1 1 3.38 0l-.02.07A1.65 1.65 0 0 0 5 8.6 1.65 1.65 0 0 0 6.82 8l.06-.06a2 2 0 1 1 2.83 2.83L9.65 11a1.65 1.65 0 0 0 .35 1.94 1.65 1.65 0 0 0 1.94.35L13 12.35a1.65 1.65 0 0 0 1.94-.35l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 15z"></path>
            </svg>
        </div>
    </div>

    <!-- MINZZ CASH -->
    <div class="cash-card">
        <div class="cash-header">
            <div class="cash-left">
                <img src="https://files.catbox.moe/apv73t.png" alt="Minzz Cash">
                <span class="cash-title">Minzz Cash</span>
            </div>
            <div class="cash-btns">
             <a href="https://wa.me/601131209082"
   target="_blank"
   class="btn-topup new-topup"
   style="text-decoration:none;">Top Up</a>
                <button class="btn-redeem">Redeem</button>
            </div>
        </div>
        <div class="cash-amount">
            <?php echo number_format($balance,0); ?> MZC
        </div>
    </div>

    <!-- STATUS CARDS -->
    <div class="status-grid">
        <div class="status-card status-pending">
            <div class="status-number"><?php echo $pending; ?></div>
            Menunggu
        </div>
        <div class="status-card status-processing">
            <div class="status-number"><?php echo $processing; ?></div>
            Dalam Proses
        </div>
        <div class="status-card status-success">
            <div class="status-number"><?php echo $success; ?></div>
            Sukses
        </div>
        <div class="status-card status-failed">
            <div class="status-number"><?php echo $failed; ?></div>
            Gagal
        </div>
    </div>

    <!-- HISTORY -->
    <div class="history-title">Riwayat Transaksi Terbaru Hari Ini</div>

    <?php if ($historyRows->num_rows > 0): ?>
        <table>
            <tr>
                <th>No. Invoice</th>
                <th>ID Trx</th>
                <th>Item</th>
                <th>User Input</th>
                <th>Harga</th>
                <th>Tarikh</th>
            </tr>
            <?php while($r = $historyRows->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['external_ref']); ?></td>
                    <td><?php echo htmlspecialchars($r['product_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($r['target_account']); ?></td>
                    <td><?php echo number_format($r['amount']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="empty-box">
            <img src="../assets/img/icons/empty.png" alt="No data">
            <div style="font-size:17px;">Data tidak ditemukan!</div>
            <div style="font-size:12px;">Tidak ada aktiviti data.</div>
        </div>
    <?php endif; ?>

</div><!-- /.main-container -->

<?php include '../include/footer.php'; ?>

<!-- MODAL EDIT PROFILE -->
<div class="modal-backdrop" id="editProfileModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Profil</div>
            <div class="modal-close" id="modalCloseBtn">&times;</div>
        </div>

        <form action="profile_update.php" method="post">
            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">

            <div class="form-group">
                <div class="form-label">Nama Penuh</div>
                <input type="text" name="name" class="form-control"
                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <div class="form-label">No. Telefon</div>
                <input type="text" name="phone" class="form-control"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <!-- Kau boleh tambah email / password change kat sini nanti -->

            <button type="submit" class="form-submit">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script>
    const settingsBtn = document.getElementById('profileSettingsBtn');
    const modal       = document.getElementById('editProfileModal');
    const modalClose  = document.getElementById('modalCloseBtn');

    if (settingsBtn && modal) {
        settingsBtn.addEventListener('click', () => {
            modal.classList.add('active');
        });
    }
    if (modalClose && modal) {
        modalClose.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }
    // Tutup bila klik luar box
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }
</script>

</body>
</html>