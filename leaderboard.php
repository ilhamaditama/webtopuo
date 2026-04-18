<?php
// public/leaderboard.php

// DEBUG – buang kalau dah siap production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/app.php';
require_once '../config/db.php';

// ================ HELPER ================ //
function mask_name($name){
    $name = trim($name);
    if ($name === '') return '-';
    // ambil 3 huruf pertama, selebihnya jadi *
    $first = mb_substr($name, 0, 3, 'UTF-8');
    return $first . str_repeat('*', max(3, mb_strlen($name, 'UTF-8') - 3));
}

function format_rm($amount){
    $amount = floatval($amount ?? 0);
    return 'RM ' . number_format($amount, 2, '.', ',');
}

// senarai range
$ranges = [
    'today' => [
        'label' => 'Top 10 - Hari Ini',
        'where' => "DATE(t.created_at) = CURDATE()"
    ],
    'week' => [
        'label' => 'Top 10 - Minggu Ini',
        'where' => "YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1)"
    ],
    'month' => [
        'label' => 'Top 10 - Bulan Ini',
        'where' => "YEAR(t.created_at) = YEAR(CURDATE()) 
                    AND MONTH(t.created_at) = MONTH(CURDATE())"
    ],
    'all' => [
        'label' => 'Top 10 - Keseluruhan',
        'where' => "1=1"
    ],
];

$boards = [];
foreach ($ranges as $key => $cfg) {
    $whereDate = $cfg['where'];

    // ikut keperluan kau, boleh buang filter status ni
    $sql = "
        SELECT 
            t.user_id,
            u.name,
            SUM(t.amount) AS total_amount
        FROM transactions t
        LEFT JOIN users u ON u.id = t.user_id
        WHERE 
            $whereDate
        GROUP BY t.user_id
        HAVING total_amount > 0
        ORDER BY total_amount DESC
        LIMIT 10
    ";

    $res = $conn->query($sql);
    if (!$res) {
        // kalau query salah, kita tunjuk error terus (supaya tak blank putih)
        die('SQL Error ('.$key.'): ' . $conn->error);
    }

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $boards[$key] = $rows;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --bg-section: #020617;
            --text-main: #ffffff;
            --text-muted: #9ca3af;
            --accent: #f97316;
            --card-bg: #020617;
            --card-border: #111827;
        }
        *{ box-sizing: border-box; }
        body{
            margin:0;
            font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:#020617;
            color:var(--text-main);
        }
        a{ color:inherit; text-decoration:none; }

        .page-wrap{
            background:var(--bg-section);
            min-height:100vh;
        }
        .main-container{
            max-width:1100px;
            margin:0 auto;
            padding:14px 16px 40px;
        }

        /* Title section */
        .lb-subtitle{
            font-size:14px;
            color:var(--accent);
            font-weight:600;
            margin-top:10px;
            margin-bottom:4px;
        }
        .lb-title{
            font-size:28px;
            font-weight:700;
            line-height:1.25;
            margin-bottom:10px;
        }
        .lb-desc{
            font-size:13px;
            color:var(--text-muted);
            line-height:1.6;
        }

        /* Tab buttons */
        .lb-tabs{
            margin-top:22px;
            display:flex;
            gap:8px;
            overflow-x:auto;
            scrollbar-width:none;
        }
        .lb-tabs::-webkit-scrollbar{ display:none; }

        .lb-tab-btn{
            flex-shrink:0;
            padding:8px 16px;
            border-radius:999px;
            font-size:13px;
            background:#111827;
            color:#e5e7eb;
            border:1px solid #1f2937;
            cursor:pointer;
            white-space:nowrap;
        }
        .lb-tab-btn.active{
            background:#f97316;
            border-color:#fed7aa;
            color:#111827;
            font-weight:600;
        }

        /* List card */
        .lb-panel{
            margin-top:16px;
            border-radius:18px;
            background:#020617;
            border:1px solid rgba(148,163,184,0.4);
            padding:14px 12px;
        }

        .lb-panel-title{
            font-size:14px;
            font-weight:600;
            margin-bottom:8px;
        }

        .lb-list{
            list-style:none;
            padding:0;
            margin:0;
        }
        .lb-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:8px 4px;
            border-bottom:1px solid rgba(31,41,55,0.9);
            font-size:13px;
        }
        .lb-row:last-child{
            border-bottom:none;
        }

        .lb-left{
            display:flex;
            align-items:center;
            gap:8px;
            min-width:0;
        }
        .lb-rank{
            width:20px;
        }
        .lb-name{
            font-weight:500;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .lb-medal{
            font-size:14px;
            margin-left:4px;
        }
        .lb-amount{
            font-weight:600;
            font-size:13px;
        }
        .lb-empty{
            padding:18px 6px 4px;
            text-align:center;
            font-size:13px;
            color:var(--text-muted);
        }

        @media(min-width:768px){
            .lb-title{ font-size:32px; }
        }
    </style>
</head>
<body>

<div class="page-wrap">
    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="main-container">

        <div class="lb-subtitle">Leaderboard</div>
        <div class="lb-title">
            Top 10 Pembelian Terbanyak<br>
            di MinzzShop
        </div>
        <div class="lb-desc">
            Berikut ialah senarai 10 pembelian terbanyak yang dilakukan oleh pelanggan kami.
            Data ini diambil daripada sistem MinzzShop dan sentiasa dikemas kini.
        </div>

        <!-- TAB BUTTON -->
        <div class="lb-tabs">
            <button class="lb-tab-btn active" data-target="today">Hari Ini</button>
            <button class="lb-tab-btn" data-target="week">Minggu Ini</button>
            <button class="lb-tab-btn" data-target="month">Bulan Ini</button>
            <button class="lb-tab-btn" data-target="all">Keseluruhan</button>
        </div>

        <!-- PANELS -->
        <?php foreach ($ranges as $key => $cfg): ?>
            <?php
            $rows = $boards[$key] ?? [];
            ?>
            <div class="lb-panel" id="panel-<?php echo htmlspecialchars($key); ?>"
                 style="<?php echo $key === 'today' ? '' : 'display:none;'; ?>">
                <div class="lb-panel-title"><?php echo htmlspecialchars($cfg['label']); ?></div>

                <?php if (count($rows) === 0): ?>
                    <div class="lb-empty">
                        Tiada transaksi ditemui untuk tempoh ini.
                    </div>
                <?php else: ?>
                    <ul class="lb-list">
                        <?php foreach ($rows as $i => $row): ?>
                            <?php
                            $rank = $i + 1;
                            $nameMasked = mask_name($row['name'] ?? 'User');
                            $amountStr  = format_rm($row['total_amount'] ?? 0);
                            $medal = '';
                            if ($rank === 1) $medal = '🥇';
                            elseif ($rank === 2) $medal = '🥈';
                            elseif ($rank === 3) $medal = '🥉';
                            ?>
                            <li class="lb-row">
                                <div class="lb-left">
                                    <div class="lb-rank"><?php echo $rank; ?>.</div>
                                    <div class="lb-name">
                                        <?php echo htmlspecialchars($nameMasked); ?>
                                        <?php if ($medal): ?>
                                            <span class="lb-medal"><?php echo $medal; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lb-amount"><?php echo $amountStr; ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div>

    <?php include '../include/footer.php'; ?>
</div>

<script>
// tab switching
document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('.lb-tab-btn');
    buttons.forEach(function(btn){
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-target');

            // tukar active btn
            buttons.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');

            // tukar panel
            ['today','week','month','all'].forEach(function(key){
                var panel = document.getElementById('panel-' + key);
                if (!panel) return;
                panel.style.display = (key === target) ? 'block' : 'none';
            });
        });
    });
});
</script>

</body>
</html>