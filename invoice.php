<?php
// public/invoice.php
require_once '../config/app.php';
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   LOAD THEME COLOR (DB)
=============================== */
$themeColor = '#3b82f6'; // fallback
try {
    $resSet = $conn->query("SELECT theme_color FROM site_settings WHERE is_active=1 ORDER BY id ASC LIMIT 1");
    if ($resSet && $resSet->num_rows > 0) {
        $rowSet = $resSet->fetch_assoc();
        if (!empty($rowSet['theme_color'])) {
            $themeColor = trim($rowSet['theme_color']);
        }
    }
} catch (Throwable $e) {
    // kalau query gagal, guna fallback
}

// sanitize simple hex
if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $themeColor)) {
    $themeColor = '#3b82f6';
}

/**
 * Ambil order berdasarkan:
 *  - ?order_id=MINZZ... (custom_order_id)
 *  - fallback: ?order_id=ID numeric (id primary)
 */
$orderParam = $_GET['order_id'] ?? null;

if (!$orderParam) {
    http_response_code(400);
    echo "Order ID tidak sah.";
    exit;
}

$order = null;

/* ===============================
   CUBA CARI IKUT custom_order_id
=============================== */
$stmt = $conn->prepare("
    SELECT t.*,
           p.product_name,
           p.srv_code,
           g.name   AS game_name,
           g.icon   AS game_icon,
           g.publisher,
           pm.name  AS payment_method_name
    FROM transactions t
    LEFT JOIN products p       ON p.id = t.product_id
    LEFT JOIN games g          ON g.id = t.game_id
    LEFT JOIN payment_methods pm ON pm.id = t.payment_method_id
    WHERE t.custom_order_id = ?
    LIMIT 1
");
$stmt->bind_param('s', $orderParam);
$stmt->execute();
$res   = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

/* ======================================
   KALAU TAK JUMPA, CUBA ID NUMERIC BIASA
====================================== */
if (!$order && ctype_digit($orderParam)) {
    $idNumeric = (int)$orderParam;

    $stmt = $conn->prepare("
        SELECT t.*,
               p.product_name,
               p.srv_code,
               g.name   AS game_name,
               g.icon   AS game_icon,
               g.publisher,
               pm.name  AS payment_method_name
        FROM transactions t
        LEFT JOIN products p       ON p.id = t.product_id
        LEFT JOIN games g          ON g.id = t.game_id
        LEFT JOIN payment_methods pm ON pm.id = t.payment_method_id
        WHERE t.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $idNumeric);
    $stmt->execute();
    $res   = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();
}

if (!$order) {
    http_response_code(404);
    echo "Order tidak dijumpai.";
    exit;
}

/* ============== FORMAT DATA / HELPER ============== */
function format_rm($amount) {
    return 'RM' . number_format((float)$amount, 2, '.', ',');
}

$customOrderId = $order['custom_order_id'] ?: ('MINZZ-' . $order['id']);
$gameName      = $order['game_name'] ?: 'Game';
$gameIcon      = $order['game_icon'] ?: (BASE_URL . 'assets/img/icons/game-default.png');
$productName   = $order['product_name'] ?: 'Produk';
$publisher     = $order['publisher'] ?: '';
$userGameId    = $order['target_account'];   // contoh: "12345678(1)"
$qty           = (int)($order['qty'] ?? 1);
$amount        = (float)$order['amount'];
$status        = $order['status'] ?? 'pending';

// fee – kalau kau ada column khas, boleh tukar sini. Buat masa ni 0
$fee           = 0.00;
$subtotal      = $amount;
$total         = $subtotal + $fee;

// Masa expired: created_at + 3 jam
$createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
$createdTs = strtotime($createdAt);
$expiryTs  = $createdTs + (3 * 60 * 60); // +3 jam
$expiryMs  = $expiryTs * 1000;

// Label status
function status_label($status) {
    switch ($status) {
        case 'waiting_payment': return 'Menunggu Pembayaran';
        case 'paid':            return 'Dibayar';
        case 'processing':      return 'Sedang Diproses';
        case 'success':         return 'Berjaya';
        case 'failed':          return 'Gagal';
        default:                return ucfirst($status);
    }
}

$statusText = status_label($status);

// Payment method – ikut table payment_methods. Fallback Minzz Cash.
$paymentMethodName = $order['payment_method_name'] ?: 'Minzz Cash';

/* =====================
   RATING (1x per order)
===================== */

$currentUserId   = $_SESSION['user_id'] ?? null;
$transactionId   = (int)$order['id'];
$ratingError     = '';
$ratingSuccess   = '';
$existingRating  = null;

// Semak kalau user dah pernah bagi rating untuk transaksi ni
if ($currentUserId) {
    $stmtR = $conn->prepare("
        SELECT rating, review, created_at
        FROM order_ratings
        WHERE transaction_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmtR->bind_param('ii', $transactionId, $currentUserId);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    $existingRating = $resR->fetch_assoc();
    $stmtR->close();
}

// Handle POST rating
if ($currentUserId && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {

    // Jika dah ada rating, jangan benarkan lagi
    if ($existingRating) {
        $ratingError = 'Anda sudah memberikan rating untuk pesanan ini.';
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $review = trim($_POST['review'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $ratingError = 'Sila pilih rating antara 1 hingga 5 bintang.';
        } else {
            $stmtIns = $conn->prepare("
                INSERT INTO order_ratings (transaction_id, user_id, rating, review, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtIns->bind_param('iiis', $transactionId, $currentUserId, $rating, $review);
            if ($stmtIns->execute()) {
                $ratingSuccess = 'Terima kasih! Rating anda telah direkodkan.';
                $existingRating = [
                    'rating' => $rating,
                    'review' => $review,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $ratingError = 'Ralat semasa menyimpan rating. Cuba lagi.';
            }
            $stmtIns->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo htmlspecialchars($customOrderId); ?> - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --bg-card: #020617;

            /* THEME COLOR FROM DB */
            --theme: <?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;

            /* derived (simple) */
            --theme-rgb: 59,130,246; /* fallback kalau browser tak support color-mix */
            --danger: #f97373;

            --text-main: #ffffff;
            --text-muted: #9ca3af;
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

        .invoice-container{
            max-width:900px;
            margin:0 auto;
            padding:0 14px 40px;
        }

        .top-spacing{ height:16px; }

        /* TIMER */
        .timer-wrap{
            margin-top:18px;
            display:flex;
            justify-content:flex-start;
        }
        .timer-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 14px;
            border-radius:999px;

            background: color-mix(in srgb, var(--theme) 18%, transparent);
            border:1px solid color-mix(in srgb, var(--theme) 70%, transparent);
            box-shadow:0 8px 18px rgba(15,23,42,0.8);

            font-size:12px;
        }
        /* fallback kalau color-mix tak support */
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            .timer-pill{
                background: rgba(37,99,235,0.12);
                border:1px solid rgba(59,130,246,0.6);
            }
        }

        .timer-pill span.value{
            font-weight:700;
            color:#f9fafb;
        }
        .timer-pill span.label{
            color:#cbd5f5;
        }

        /* CARDS */
        .card{
            margin-top:18px;
            border-radius:24px;
            background:#020617;
            border:1px solid rgba(31,41,55,0.95);
            box-shadow:0 18px 40px rgba(0,0,0,0.9);
            padding:14px 16px 16px;
        }

        /* Account info card */
        .account-card{
            display:flex;
            gap:14px;
            align-items:center;
        }
        .game-cover{
            width:96px;
            height:96px;
            border-radius:24px;
            overflow:hidden;
            flex-shrink:0;
            background:#020617;
        }
        .game-cover img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .account-text{
            flex:1;
            min-width:0;
        }
        .account-title{
            font-size:13px;
            color:var(--text-muted);
            margin-bottom:4px;
        }
        .game-name{
            font-size:16px;
            font-weight:700;
            margin-bottom:3px;
        }
        .product-name{
            font-size:13px;
            color:#e5e7eb;
            margin-bottom:6px;
        }
        .account-id-row{
            font-size:13px;
            color:#e5e7eb;
        }
        .account-id-row span.label{
            color:var(--text-muted);
        }

        /* Status badge */
        .status-badge{
            display:inline-flex;
            align-items:center;
            padding:4px 10px;
            border-radius:999px;
            font-size:11px;

            background: color-mix(in srgb, var(--theme) 18%, transparent);
            border:1px solid color-mix(in srgb, var(--theme) 75%, transparent);
            color: #e5e7eb;
        }
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            .status-badge{
                background:rgba(37,99,235,0.15);
                border:1px solid rgba(59,130,246,0.7);
                color:#bfdbfe;
            }
        }

        .status-badge.failed{
            background:rgba(220,38,38,0.18);
            border-color:rgba(248,113,113,0.9);
            color:#fecaca;
        }
        .status-row{
            margin-top:6px;
            font-size:12px;
            color:var(--text-muted);
        }

        /* Section title row (like "Rincian Pembayaran") */
        .section-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            font-size:14px;
            font-weight:600;
        }

        .chevron{ font-size:16px; opacity:0.6; }

        /* payment details table */
        .details-table{
            margin-top:10px;
            font-size:13px;
        }
        .details-row{
            display:flex;
            justify-content:space-between;
            padding:6px 0;
        }
        .details-row + .details-row{
            border-top:1px solid rgba(31,41,55,0.9);
        }
        .details-row .label{ color:#e5e7eb; }
        .details-row .value{ font-weight:500; }

        .total-box{
            margin-top:18px;
            border-radius:18px;

            border:1px solid color-mix(in srgb, var(--theme) 80%, transparent);
            padding:10px 12px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:14px;
            font-weight:600;

            background: radial-gradient(circle at top,
                color-mix(in srgb, var(--theme) 22%, transparent),
                #020617 70%
            );
        }
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            .total-box{
                border:1px solid rgba(59,130,246,0.9);
                background: radial-gradient(circle at top,rgba(37,99,235,0.2),#020617 70%);
            }
        }
        .total-box .label{ color:#e5e7eb; }
        .total-box .value{ color: var(--theme); }

        /* Payment method block */
        .pm-label{
            margin-top:24px;
            font-size:13px;
            color:var(--text-muted);
        }
        .pm-card{
            margin-top:8px;
            border-radius:18px;
            background:#020617;
            border:1px solid rgba(31,41,55,0.95);
            padding:10px 12px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:14px;
        }
        .pm-name{ font-weight:600; }

        /* Rating card */
        .rating-note{
            margin-top:20px;
            font-size:13px;
            color:var(--text-muted);
        }
        .rating-card{
            margin-top:8px;
            border-radius:20px;
            background:#020617;
            border:1px solid rgba(31,41,55,0.95);
            padding:12px 14px 14px;
        }
        .rating-title{
            font-size:14px;
            font-weight:600;
            margin-bottom:6px;
        }
        .rating-stars{
            margin:6px 0 10px;
            display:flex;
            gap:6px;
            font-size:18px;
        }
        .rating-stars label{ cursor:pointer; }
        .rating-stars input{ display:none; }
        .rating-stars span.star{ transition: transform .1s ease; }
        .rating-stars input:checked + span.star{ transform: scale(1.1); }

        .rating-textarea{
            width:100%;
            min-height:70px;
            border-radius:10px;
            border:1px solid rgba(55,65,81,0.9);
            background:#020617;
            color:#e5e7eb;
            font-size:13px;
            padding:8px 10px;
            resize:vertical;
        }
        .rating-buttons{
            margin-top:8px;
            display:flex;
            justify-content:flex-end;
        }
        .rating-submit-btn{
            border:none;
            border-radius:999px;
            padding:8px 16px;
            font-size:13px;
            font-weight:600;
            cursor:pointer;

            background: linear-gradient(135deg,
                color-mix(in srgb, var(--theme) 60%, #ffffff 0%),
                var(--theme)
            );
            color:#f9fafb;
        }
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            .rating-submit-btn{
                background:linear-gradient(135deg,#38bdf8,#2563eb);
            }
        }

        .rating-msg{ margin-top:6px; font-size:12px; }
        .rating-msg.error{ color:var(--danger); }
        .rating-msg.success{ color:#4ade80; }

        .footer-mini{
            margin-top:26px;
            text-align:center;
            font-size:11px;
            color:#6b7280;
        }
    </style>
</head>
<body>

<div class="page">

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <main class="invoice-container">
        <div class="top-spacing"></div>

        <!-- TIMER -->
        <div class="timer-wrap">
            <div class="timer-pill" id="timerPill">
                <span class="value" id="timerHours">0</span><span class="label">Jam</span>
                <span class="value" id="timerMinutes">0</span><span class="label">Minit</span>
                <span class="value" id="timerSeconds">0</span><span class="label">Saat</span>
            </div>
        </div>

        <!-- ACCOUNT / GAME INFO -->
        <section class="card">
            <div class="account-card">
                <div class="game-cover">
                    <img src="<?php echo htmlspecialchars($gameIcon); ?>"
                         alt="<?php echo htmlspecialchars($gameName); ?>">
                </div>
                <div class="account-text">
                    <div class="account-title">Informasi Akaun</div>
                    <div class="game-name"><?php echo htmlspecialchars($gameName); ?></div>
                    <div class="product-name">
                        <?php echo htmlspecialchars($productName); ?>
                    </div>
                    <div class="account-id-row">
                        <span class="label">ID: </span>
                        <span><?php echo htmlspecialchars($userGameId); ?></span>
                    </div>
                    <div class="status-row">
                        <span class="<?php echo in_array($status, ['failed','cancelled']) ? 'status-badge failed' : 'status-badge'; ?>">
                            Status: <?php echo htmlspecialchars($statusText); ?>
                        </span>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <span style="font-size:11px;">Order: <?php echo htmlspecialchars($customOrderId); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- PAYMENT DETAILS -->
        <section class="card">
            <div class="section-header">
                <span>Rincian Pembayaran</span>
                <span class="chevron">&#9660;</span>
            </div>

            <div class="details-table">
                <div class="details-row">
                    <span class="label">Harga</span>
                    <span class="value"><?php echo format_rm($subtotal); ?></span>
                </div>
                <div class="details-row">
                    <span class="label">Jumlah</span>
                    <span class="value"><?php echo $qty; ?>x</span>
                </div>
                <div class="details-row">
                    <span class="label">Subtotal</span>
                    <span class="value"><?php echo format_rm($subtotal); ?></span>
                </div>
                <div class="details-row">
                    <span class="label">Biaya</span>
                    <span class="value"><?php echo format_rm($fee); ?></span>
                </div>
            </div>

            <div class="total-box">
                <span class="label">Total Pembayaran</span>
                <span class="value"><?php echo format_rm($total); ?></span>
            </div>
        </section>

        <!-- PAYMENT METHOD -->
        <div class="pm-label">Metode Pembayaran</div>
        <div class="pm-card">
            <div class="pm-name"><?php echo htmlspecialchars($paymentMethodName); ?></div>
            <div style="font-size:12px;color:var(--text-muted);">
                Tiada caj tambahan
            </div>
        </div>

        <!-- RATING SECTION -->
        <div class="rating-note">
            Kongsi pengalaman anda untuk pesanan ini.
        </div>

        <section class="rating-card">
            <div class="rating-title">Rating Pesanan</div>

            <?php if (!$currentUserId): ?>
                <div class="rating-msg error">
                    Sila log masuk untuk memberi rating.
                </div>
            <?php elseif ($existingRating): ?>
                <div class="rating-msg success">
                    Terima kasih! Anda telah memberi rating
                    <strong><?php echo (int)$existingRating['rating']; ?>/5</strong>
                    untuk pesanan ini.
                </div>
                <?php if (!empty($existingRating['review'])): ?>
                    <div style="margin-top:8px;font-size:13px;color:#e5e7eb;">
                        "<?php echo nl2br(htmlspecialchars($existingRating['review'])); ?>"
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($ratingError): ?>
                    <div class="rating-msg error"><?php echo htmlspecialchars($ratingError); ?></div>
                <?php elseif ($ratingSuccess): ?>
                    <div class="rating-msg success"><?php echo htmlspecialchars($ratingSuccess); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label>
                                <input type="radio" name="rating" value="<?php echo $i; ?>">
                                <span class="star"><?php echo $i <= 3 ? '⭐' : '🌟'; ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                    <textarea
                        name="review"
                        class="rating-textarea"
                        placeholder="Beritahu kami pengalaman anda (optional)..."></textarea>

                    <div class="rating-buttons">
                        <button type="submit" name="submit_rating" class="rating-submit-btn">
                            Hantar Rating
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <div class="footer-mini">
            © <?php echo date('Y'); ?> <?php echo APP_NAME; ?> – Semua hak cipta terpelihara.
        </div>
    </main>

    <?php include '../include/footer.php'; ?>

</div>

<script>
// Countdown timer
const expiryMs   = <?php echo json_encode($expiryMs); ?>;
const timerHours   = document.getElementById('timerHours');
const timerMinutes = document.getElementById('timerMinutes');
const timerSeconds = document.getElementById('timerSeconds');
const timerPill    = document.getElementById('timerPill');

function updateTimer(){
    const now   = Date.now();
    let diff    = expiryMs - now;

    if (diff <= 0){
        timerHours.textContent   = '0';
        timerMinutes.textContent = '0';
        timerSeconds.textContent = '0';
        timerPill.style.background   = 'rgba(127,29,29,0.35)';
        timerPill.style.borderColor  = 'rgba(248,113,113,0.9)';
        timerPill.textContent        = 'Tempoh pembayaran tamat';
        clearInterval(timerInterval);
        return;
    }

    const sec  = Math.floor(diff / 1000);
    const h    = Math.floor(sec / 3600);
    const m    = Math.floor((sec % 3600) / 60);
    const s    = sec % 60;

    timerHours.textContent   = h;
    timerMinutes.textContent = m.toString().padStart(2,'0');
    timerSeconds.textContent = s.toString().padStart(2,'0');
}

const timerInterval = setInterval(updateTimer, 1000);
updateTimer();
</script>

</body>
</html>