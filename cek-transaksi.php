<?php
// public/cek-transaksi.php
require_once '../config/app.php';
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ========================
   FUNCTIONS
======================== */
function format_rm($amount) {
    return 'RM' . number_format((float)$amount, 2, '.', ',');
}

function mask_middle($str, $prefix = 4, $suffix = 4, $maskChar = '*') {
    $str = (string)$str;
    $len = strlen($str);
    if ($len <= ($prefix + $suffix)) return $str;

    $start  = substr($str, 0, $prefix);
    $end    = substr($str, -$suffix);
    $middle = str_repeat($maskChar, $len - $prefix - $suffix);
    return $start . $middle . $end;
}

function mask_phone($phone) {
    $phone = preg_replace('/\D+/', '', (string)$phone);
    $len   = strlen($phone);
    if ($len < 4) return $phone;

    $prefix = 3;
    $suffix = 3;
    if ($len <= ($prefix + $suffix)) $suffix = 2;

    $start  = substr($phone, 0, $prefix);
    $end    = substr($phone, -$suffix);
    $middle = str_repeat('*', max(0, $len - $prefix - $suffix));

    return $start . $middle . $end;
}

function status_badge_class($status) {
    $s = strtolower((string)$status);
    if (in_array($s, ['success', 'completed', 'done'])) return 'badge-success';
    if (in_array($s, ['paid', 'processing', 'processed'])) return 'badge-paid';
    if (in_array($s, ['pending', 'waiting_payment'])) return 'badge-pending';
    if (in_array($s, ['failed', 'cancelled', 'canceled'])) return 'badge-default';
    return 'badge-default';
}

/* ========================
   HANDLE SEARCH (REDIRECT)
======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceSearch = trim($_POST['invoice'] ?? '');
    if ($invoiceSearch !== '') {
        header('Location: ' . BASE_URL . 'invoice.php?order_id=' . urlencode($invoiceSearch));
        exit;
    }
}

/* ========================
   GET LATEST TRANSACTIONS
   IMPORTANT: ORDER BY id DESC (paling reliable)
======================== */
$sql = "
    SELECT 
        t.id,
        t.created_at,
        t.custom_order_id,
        t.target_account,
        t.amount,
        t.status
    FROM transactions t
    ORDER BY t.id DESC
    LIMIT 30
";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Cek Transaksi - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --bg-card: #020617;
            --bg-card-soft: #020b1f;

            --accent-1: #38bdf8;
            --accent-2: #2563eb;

            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --danger: #f97373;

            --table-header-bg: #020617;
        }

        *{ box-sizing:border-box; }

        body{
            margin:0;
            font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background: radial-gradient(circle at top,#0f172a 0,#020617 60%);
            color:var(--text-main);
        }

        a{ color:inherit; text-decoration:none; }

        .page{
            min-height:100vh;
            background:var(--bg-main);
        }

        .main-container{
            max-width: 960px;
            margin: 0 auto;
            padding: 0 12px 40px;
        }

        .top-space{ height:16px; }

        /* HERO */
        .hero-card{
            margin-top:20px;
            border-radius:26px;
            padding:22px 18px 20px;
            background: radial-gradient(circle at top, #020b1f, #020617 65%);
            box-shadow:0 18px 40px rgba(0,0,0,0.9);
            border:1px solid rgba(30,64,175,0.7);
        }
        .hero-title{
            font-size:22px;
            font-weight:700;
            margin-bottom:4px;
        }
        .hero-sub{
            font-size:13px;
            color:var(--text-muted);
        }

        /* SEARCH CARD */
        .search-card{
            margin-top:14px;
            border-radius:26px;
            padding:18px 18px 16px;
            background: #020617;
            border:1px solid rgba(30,64,175,0.7);
            box-shadow:0 18px 40px rgba(0,0,0,0.85);
        }

        .search-label{
            font-size:14px;
            font-weight:500;
            margin-bottom:10px;
        }

        .search-input-wrap{
            background:#020b1f;
            border-radius:18px;
            padding:7px;
            display:flex;
            gap:6px;
            align-items:center;
            margin-bottom:10px;
        }
        .search-input{
            flex:1;
            border:none;
            outline:none;
            font-size:13px;
            padding:9px 12px;
            border-radius:14px;
            background:transparent;
            color:var(--text-main);
        }
        .search-input::placeholder{
            color:#6b7280;
        }

        .search-btn{
            width:100%;
            border:none;
            border-radius:16px;
            padding:10px 12px;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            background: linear-gradient(135deg,var(--accent-1),var(--accent-2));
            color:#f9fafb;
            box-shadow:0 10px 25px rgba(15,23,42,0.9);
        }

        /* SECTION TITLE */
        .section-title-wrap{
            margin-top:22px;
            margin-bottom:8px;
        }
        .section-title{
            font-size:18px;
            font-weight:600;
        }
        .section-sub{
            font-size:13px;
            color:var(--text-muted);
            margin-top:2px;
        }

        /* TABLE SCROLL */
        .table-scroll{
            width:100%;
            overflow-x:auto;
            overflow-y:hidden;
            -webkit-overflow-scrolling:touch;
            margin-top:8px;
            padding-bottom:10px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
            min-width: 650px;
        }

        thead{
            background:var(--table-header-bg);
        }

        th, td{
            padding:8px 10px;
            text-align:left;
            white-space:nowrap;
        }

        th{
            font-weight:600;
            font-size:12px;
            color:#e5e7eb;
            border-bottom:1px solid rgba(55,65,81,0.9);
        }

        tbody tr:nth-child(even){
            background:#020b1f;
        }
        tbody tr:nth-child(odd){
            background:#020617;
        }

        tbody tr:hover{
            background:#020f25;
        }

        td{
            border-bottom:1px solid rgba(30,41,59,0.8);
            color:#e5e7eb;
        }

        .text-right{
            text-align:right;
        }

        /* BADGES */
        .status-badge{
            display:inline-flex;
            align-items:center;
            padding:4px 10px;
            border-radius:999px;
            font-size:11px;
            font-weight:600;
        }
        .badge-success{
            background:#16a34a;
            color:#f9fafb;
        }
        .badge-paid{
            background:#facc15;
            color:#111827;
        }
        .badge-pending{
            background:#f97316;
            color:#111827;
        }
        .badge-default{
            background:#4b5563;
            color:#f9fafb;
        }

        @media (max-width: 480px){
            .hero-title{ font-size:20px; }
        }
    </style>
</head>
<body>

<div class="page">

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <main class="main-container">
        <div class="top-space"></div>

        <!-- HERO -->
        <section class="hero-card">
            <div class="hero-title">Check Your Transactions Easily</div>
            <div class="hero-sub">
                Enter the invoice number to see the status of your order, or see a list of recent transactions below.
            </div>
        </section>

        <!-- SEARCH CARD -->
        <section class="search-card">
            <div class="search-label">Enter your invoice number</div>
            <form method="post">
                <div class="search-input-wrap">
                    <input
                        type="text"
                        name="invoice"
                        class="search-input"
                        placeholder="Contoh: MINZZ202501011234"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="search-btn">
                    Search Invoice
                </button>
            </form>
        </section>

        <!-- LATEST TRANSACTIONS -->
        <div class="section-title-wrap">
            <div class="section-title">30 Latest Order</div>
            <div class="section-sub">
                The data of the 30 most recent orders is updated at any time
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>No. HP</th>
                        <th class="text-right">Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($res && $res->num_rows > 0): ?>
                    <?php while ($row = $res->fetch_assoc()): ?>
                        <?php
                            $created = $row['created_at'] ?? '';
                            $dateStr = $created ? date('d/m/Y H:i', strtotime($created)) : '-';

                            $invoiceRaw    = $row['custom_order_id'] ?: '-';
                            $invoiceMasked = ($invoiceRaw === '-') ? '-' : mask_middle($invoiceRaw, 4, 4);

                            $phoneRaw      = $row['target_account'] ?: '****';
                            $phoneMasked   = ($phoneRaw === '****') ? '****' : mask_phone($phoneRaw);

                            $amount  = (float)$row['amount'];
                            $status  = $row['status'] ?? 'pending';
                            $badgeCl = status_badge_class($status);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dateStr); ?></td>
                            <td><?php echo htmlspecialchars($invoiceMasked); ?></td>
                            <td><?php echo htmlspecialchars($phoneMasked); ?></td>
                            <td class="text-right"><?php echo format_rm($amount); ?></td>
                            <td>
                                <span class="status-badge <?php echo $badgeCl; ?>">
                                    <?php echo strtoupper(htmlspecialchars((string)$status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tiada transaksi direkodkan lagi.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <?php include '../include/footer.php'; ?>

</div>

</body>
</html>