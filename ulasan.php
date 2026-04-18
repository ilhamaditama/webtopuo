<?php
// public/ulasan.php
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
    // fallback
}

// sanitize simple hex
if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $themeColor)) {
    $themeColor = '#3b82f6';
}

/* ========================
   CONFIG
======================== */
$perPage = 5;

/* ========================
   HELPER FUNCTIONS
======================== */
function mask_account($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') return '******';

    $len = strlen($raw);
    if ($len <= 3) return $raw . '***';

    return substr($raw, 0, 3) . str_repeat('*', $len - 3);
}

function render_stars($rating)
{
    $rating = max(1, min(5, (int)$rating));
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="star ' . ($i <= $rating ? 'filled' : '') . '">★</span>';
    }
    return $html;
}

function render_review_card($r)
{
    $gameName = $r['game_name'] ?: 'Game';
    $productName = $r['product_name'] ?: '';
    $review = $r['review'] ?: '';
    $rating = (int)$r['rating'];
    $date = date('d-m-Y H:i:s', strtotime($r['created_at']));
    $maskedAcc = mask_account($r['target_account']);

    ob_start();
    ?>
    <article class="review-card">
        <div class="review-header">
            <div class="game-name"><?php echo htmlspecialchars($gameName); ?></div>
        </div>

        <div class="review-body">
            <?php if ($review): ?>
                <p class="review-comment">“<?php echo htmlspecialchars($review); ?>”</p>
            <?php endif; ?>

            <div class="review-meta-row">
                <div class="review-user"><?php echo htmlspecialchars($maskedAcc); ?></div>
                <div class="review-stars"><?php echo render_stars($rating); ?></div>
            </div>

            <div class="review-footer-row">
                <div class="review-product"><?php echo htmlspecialchars($productName); ?></div>
                <div class="review-date"><?php echo $date; ?></div>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/* ========================
   GET REVIEWS
======================== */
function get_reviews($conn, $limit, $offset)
{
    $sql = "
        SELECT 
            r.id,
            r.rating,
            r.review,
            r.created_at,
            t.target_account,
            p.product_name,
            g.name AS game_name
        FROM order_ratings r
        JOIN transactions t ON t.id = r.transaction_id
        LEFT JOIN products p ON p.id = t.product_id
        LEFT JOIN games g ON g.id = t.game_id
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

/* ========================
   AJAX LOAD MORE
======================== */
if (isset($_GET['ajax']) && $_GET['ajax'] == "1") {
    header("Content-Type: application/json");

    $offset = (int)($_GET['offset'] ?? 0);
    $rows = get_reviews($conn, $perPage, $offset);

    // Check next page
    $countRes = $conn->query("SELECT COUNT(*) AS total FROM order_ratings");
    $total = (int)$countRes->fetch_assoc()['total'];
    $hasMore = ($offset + $perPage) < $total;

    $html = '';
    foreach ($rows as $r) $html .= render_review_card($r);

    echo json_encode([
        "success" => true,
        "html" => $html,
        "hasMore" => $hasMore
    ]);
    exit;
}

/* ========================
   FIRST LOAD
======================== */
$firstReviews = get_reviews($conn, $perPage, 0);

$countRes = $conn->query("SELECT COUNT(*) AS total FROM order_ratings");
$totalReviews = (int)$countRes->fetch_assoc()['total'];

$hasMoreFirst = $totalReviews > $perPage;

?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Ulasan Pelanggan - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main:#020617;

            /* THEME COLOR FROM DB */
            --theme: <?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;

            --text-main:#f9fafb;
            --text-muted:#9ca3af;
        }

        body{
            margin:0;
            background:radial-gradient(circle at top,#0f172a 0,#020617 60%);
            font-family:'Poppins',sans-serif;
            color:var(--text-main);
        }

        .page{
            min-height:100vh;
        }

        .container{
            max-width:900px;
            margin:0 auto;
            padding:20px 14px 60px;
        }

        h1{
            margin-top:10px;
            margin-bottom:6px;
            font-size:26px;
            font-weight:700;
        }

        /* REVIEW CARD */
        .reviews-container{
            margin-top:20px;
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .review-card{
            background:#020617;
            padding:16px 18px;
            border-radius:20px;

            border:1px solid color-mix(in srgb, var(--theme) 35%, transparent);
            box-shadow:0 8px 30px rgba(0,0,0,0.6);
        }
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            .review-card{
                border:1px solid rgba(59,130,246,0.3);
            }
        }

        .review-comment{
            font-style:italic;
            margin-bottom:10px;
        }

        .review-meta-row{
            display:flex;
            justify-content:space-between;
            margin-bottom:6px;
        }

        .review-footer-row{
            display:flex;
            justify-content:space-between;
            font-size:12px;
            color:var(--text-muted);
        }

        .star{
            color:#475569;
            font-size:18px;
        }
        .star.filled{
            color:#facc15;
        }

        /* LOAD MORE BUTTON */
        .load-more{
            margin-top:20px;
            text-align:center;
        }

        button.load-btn{
            border:none;
            padding:10px 24px;
            border-radius:999px;
            font-weight:600;
            font-size:14px;
            color:white;
            cursor:pointer;
            box-shadow:0 10px 28px rgba(15,23,42,0.8);

            background: linear-gradient(135deg,
                color-mix(in srgb, var(--theme) 55%, #ffffff 0%),
                var(--theme)
            );
        }
        @supports not (background: color-mix(in srgb, #000 10%, transparent)){
            button.load-btn{
                background:linear-gradient(135deg,#38bdf8,#2563eb);
            }
        }

        button.load-btn:disabled{
            opacity:.7;
            cursor:not-allowed;
        }
    </style>
</head>
<body>

<div class="page">
    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="container">

        <h1>Customer Reviews</h1>

        <div id="reviewsList" class="reviews-container">
            <?php
            if ($firstReviews) {
                foreach ($firstReviews as $r) echo render_review_card($r);
            } else {
                echo "<p>Belum ada ulasan.</p>";
            }
            ?>
        </div>

        <?php if ($hasMoreFirst): ?>
        <div class="load-more">
            <button id="loadMoreBtn" class="load-btn">Load More</button>
        </div>
        <?php endif; ?>

    </div>

    <?php include '../include/footer.php'; ?>
</div>

<script>
let offset = <?php echo (int)$perPage; ?>;
const btn = document.getElementById("loadMoreBtn");
const list = document.getElementById("reviewsList");

if(btn){
    btn.addEventListener("click", ()=>{
        btn.disabled = true;
        btn.innerText = "Memuat...";

        fetch("ulasan.php?ajax=1&offset=" + offset)
            .then(r=>r.json())
            .then(data=>{
                btn.disabled = false;
                btn.innerText = "Muat Lebih Banyak";

                if(data.success){
                    list.insertAdjacentHTML("beforeend", data.html);
                    offset += <?php echo (int)$perPage; ?>;

                    if(!data.hasMore){
                        btn.style.display="none";
                    }
                }
            })
            .catch(()=>{
                btn.disabled = false;
                btn.innerText = "Cuba Lagi";
            });
    });
}
</script>

</body>
</html>