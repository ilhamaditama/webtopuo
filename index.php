<?php
require_once '../config/app.php';
require_once '../config/db.php';

/* =============================
   THEME COLOR (DARI DB site_settings)
   - Ambil 1 warna sahaja (HEX #RRGGBB)
   - Fallback: #2563eb
============================= */
function is_hex_color($c){
    return is_string($c) && preg_match('/^#([A-Fa-f0-9]{6})$/', $c);
}
function hex_to_rgb($hex){
    $hex = ltrim($hex, '#');
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}
function adjust_hex($hex, $percent){
    if (!is_hex_color($hex)) return $hex;
    $percent = max(-100, min(100, (float)$percent)) / 100;
    [$r,$g,$b] = hex_to_rgb($hex);

    $calc = function($c) use ($percent){
        $c = (int)$c;
        if ($percent >= 0) $c = $c + (255 - $c) * $percent; // cerahkan
        else              $c = $c * (1 + $percent);         // gelapkan
        return max(0, min(255, (int)round($c)));
    };

    $r = $calc($r); $g = $calc($g); $b = $calc($b);
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

$themeColor = '#2563eb';
$themeRes = $conn->query("SELECT theme_color FROM site_settings ORDER BY id ASC LIMIT 1");
if ($themeRes && $themeRes->num_rows > 0) {
    $row = $themeRes->fetch_assoc();
    if (!empty($row['theme_color']) && is_hex_color($row['theme_color'])) {
        $themeColor = $row['theme_color'];
    }
}
$theme2      = adjust_hex($themeColor, 18);   // cerah sikit
$themeHover1 = adjust_hex($themeColor, -12);  // gelap sikit
$themeHover2 = adjust_hex($themeColor, 8);    // hover cerah sikit
$themeRgb    = implode(',', hex_to_rgb($themeColor));

/* =============================
   BANNERS DARI DATABASE
============================= */
$banners = [];
$bannerSql   = "SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC";
$bannerQuery = $conn->query($bannerSql);
if ($bannerQuery && $bannerQuery->num_rows > 0) {
    while ($row = $bannerQuery->fetch_assoc()) {
        $banners[] = $row;
    }
}

/* =============================
   GAMES DARI DATABASE
============================= */
$games = [];
$gamesSql   = "SELECT id, name, slug, icon, path, description, publisher, category, is_active
               FROM games
               WHERE is_active = 1
               ORDER BY id ASC";
$gamesQuery = $conn->query($gamesSql);
if ($gamesQuery && $gamesQuery->num_rows > 0) {
    while ($row = $gamesQuery->fetch_assoc()) {
        $games[] = $row;
    }
}

/* =============================
   CATEGORY LIST (UNIK DARI GAMES)
============================= */
$categories = [];
foreach ($games as $g) {
    $cat = trim($g['category'] ?? '');
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
sort($categories);

function cat_slug($s){
    $s = strtolower(trim($s ?? ''));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/* =============================
   DATA POPULER SEKARANG (STATIC)
============================= */
$popularGames = [
    [
        'name'      => 'Mobile Legends Malaysia',
        'publisher' => 'Moonton',
        'image'     => 'https://files.catbox.moe/g793a4.png',
        'link'      => BASE_URL . 'topup/mlbbmy.php',
    ],
    [
        'name'      => 'Mobile Legends Indonesia',
        'publisher' => 'Moonton',
        'image'     => 'https://files.catbox.moe/b6kbjt.png',
        'link'      => BASE_URL . 'topup/mlbbid.php',
    ],
    [
        'name'      => 'PUBG Mobile',
        'publisher' => 'Tencent Games',
        'image'     => 'https://files.catbox.moe/7uudpo.png',
        'link'      => BASE_URL . 'topup/pubgm.php',
    ],
    [
        'name'      => 'Robux Via Login',
        'publisher' => 'Roblox Corporation',
        'image'     => 'https://files.catbox.moe/67tre4.jpeg',
        'link'      => BASE_URL . 'topup/robux-login.php',
    ],
    [
        'name'      => 'Free Fire (SG/MY)',
        'publisher' => 'Garena',
        'image'     => 'https://files.catbox.moe/6jb4xb.png',
        'link'      => BASE_URL . 'topup/ffsgmy.php',
    ],
    [
        'name'      => 'Room Tournament',
        'publisher' => 'Moonton',
        'image'     => 'https://files.catbox.moe/23vazf.webp',
        'link'      => BASE_URL . 'topup/rt.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FONT: POPPINS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --bg-section: #020617;
            --text-main: #ffffff;
            --text-muted: #cbd5f5;

            /* THEME (dari DB) */
            --theme: <?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;
            --theme-2: <?php echo htmlspecialchars($theme2, ENT_QUOTES); ?>;
            --theme-hover: <?php echo htmlspecialchars($themeHover1, ENT_QUOTES); ?>;
            --theme-hover-2: <?php echo htmlspecialchars($themeHover2, ENT_QUOTES); ?>;
            --theme-rgb: <?php echo htmlspecialchars($themeRgb, ENT_QUOTES); ?>;

            /* mapping (lama biru -> theme) */
            --card-blue-1: var(--theme);
            --card-blue-2: var(--theme-2);
            --card-blue-hover-1: var(--theme-hover);
            --card-blue-hover-2: var(--theme-hover-2);
            --game-card-border-active: var(--theme-2);
        }

        *{ box-sizing: border-box; }

        body{
            margin: 0;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #0f172a 0, #020617 60%);
            color: var(--text-main);
        }

        a{ color: inherit; text-decoration: none; }

        .page-wrap{
            background: var(--bg-section);
            min-height: 100vh;
        }

        .main-container{
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 14px 40px;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.96); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* =========================
           BANNER – FULL SECTION THEME
        ========================== */
        .banner-wrap{
            margin: 12px -14px 24px;
            padding: 14px 14px 18px;
            background: rgba(var(--theme-rgb), 0.10);
            animation: fadeInScale 0.5s ease-out both;
        }

        .banner-shell{
            width: 100%;
            border-radius: 26px;
            padding: 4px;
            background: linear-gradient(135deg, rgba(var(--theme-rgb),0.35), #020b2d);
            box-shadow: 0 18px 40px rgba(0,0,0,0.85);
        }

        .banner-container{
            width: 100%;
            border-radius: 22px;
            overflow: hidden;
            background: #000;
        }

        .banner-slider{
            position: relative;
            width: 100%;
            height: 135px;
            display: flex;
            touch-action: pan-y;
            transform: translateX(0);
            transition: transform 0.4s ease;
        }

        @media (min-width: 768px){
            .banner-slider{
                height: 165px;
            }
        }

        .banner-slide{
            min-width: 100%;
            height: 100%;
        }

        .banner-slide img{
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (min-width: 768px){
            .banner-slider{ height: 220px; }
        }

        .banner-dots{
            margin-top: 8px;
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .banner-dots div{
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #64748b;
            transition: 0.2s;
        }

        .banner-dots .active{
            background: #ffffff;
            width: 18px;
        }

        /* =========================
           POPULER SEKARANG
        ========================== */
        .section-popular{
            padding: 16px 0 10px;
        }

        .section-popular-title{
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .section-popular-title .icon{ font-size: 18px; }

        .section-popular-sub{
            margin-top: 4px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .popular-grid{
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 10px;
        }

        @media (min-width: 720px){
            .popular-grid{ grid-template-columns: repeat(3, minmax(0,1fr)); }
        }

        .popular-card{
            display: flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--card-blue-1), var(--card-blue-2));
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.9);
            min-height: 70px;
            transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, background 0.15s ease-out;
            animation: fadeUp 0.45s ease-out both;
        }

        .popular-card:nth-child(1){ animation-delay: 0.05s; }
        .popular-card:nth-child(2){ animation-delay: 0.10s; }
        .popular-card:nth-child(3){ animation-delay: 0.15s; }
        .popular-card:nth-child(4){ animation-delay: 0.20s; }
        .popular-card:nth-child(5){ animation-delay: 0.25s; }
        .popular-card:nth-child(6){ animation-delay: 0.30s; }

        .popular-card:hover{
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--card-blue-hover-1), var(--card-blue-hover-2));
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.9);
        }

        .popular-card-icon{
            flex-shrink: 0;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            overflow: hidden;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.14);
        }

        .popular-card-icon img{
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .popular-card-text{
            flex: 1;
            min-width: 0;
            margin-left: 8px;
        }

        .popular-card-text .title{
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .popular-card-text .subtitle{
            font-size: 11px;
            opacity: 0.9;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================
           TAB CATEGORY (DARI DB)
        ========================== */
        .category-tabs-wrap{
            margin-top: 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .category-arrow{
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid rgba(var(--theme-rgb),0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            background: linear-gradient(135deg,var(--theme-hover),var(--theme));
            color: #e5e7eb;
        }

        .category-tabs{
            flex: 1;
            display: flex;
            overflow-x: auto;
            gap: 6px;
            scrollbar-width: none;
        }

        .category-tabs::-webkit-scrollbar{ display: none; }

        .category-pill{
            flex-shrink: 0;
            padding: 7px 18px;
            border-radius: 999px;
            background: linear-gradient(135deg,var(--theme),var(--theme-2));
            color: #ffffff;
            font-size: 13px;
            border: 1px solid rgba(var(--theme-rgb),0.35);
            box-shadow: 0 6px 16px rgba(var(--theme-rgb),0.45);
            white-space: nowrap;
            cursor: pointer;
        }

        .category-pill.active{
            background: #ffffff;
            color: #0f172a;
            font-weight: 600;
            border-color: rgba(var(--theme-rgb),0.25);
            box-shadow: 0 8px 22px rgba(148,163,184,0.8);
        }

        /* =========================
           GAME GRID – PURE IMAGE + OVERLAY
        ========================== */
        .games-section{
            margin-top: 20px;
        }

        .games-grid{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .game-card{
            display: block;
            padding: 0;
            background: transparent;
            border: none;
            box-shadow: none;
            position: relative;
        }

        .game-card-img{
            position: relative;
            width: 100%;
            height: 160px;
            border-radius: 18px;
            overflow: hidden;
        }

        @media(min-width:768px){ .game-card-img{ height: 180px; } }
        @media(min-width:1024px){ .game-card-img{ height: 200px; } }

        .game-card-img img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: transform 0.18s ease-out;
        }

        .game-card-overlay{
            position: absolute;
            left: 0; right: 0; bottom: 0;
            padding: 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0));
            opacity: 0;
            transform: translateY(15px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }

        .overlay-title{
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .overlay-sub{
            font-size: 11px;
            color: #e5e7eb;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .game-card:hover .game-card-overlay,
        .game-card.show-overlay .game-card-overlay{
            opacity: 1;
            transform: translateY(0);
        }

        .game-card:active img,
        .game-card.show-overlay img{
            transform: scale(0.96);
        }

        /* ==== GAME CARD ANIMATION + LOAD MORE ==== */
        .games-grid .game-card{
            display: none;
            opacity: 0;
            transform: translateY(12px);
        }

        .games-grid .game-card.visible{
            animation: fadeUp 0.45s ease-out forwards;
        }

        .load-more-wrap{
            margin-top: 18px;
            text-align: center;
        }

        .load-more-btn{
            padding: 9px 22px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.8);
            background: linear-gradient(135deg,var(--theme),var(--theme-2));
            color: #f9fafb;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(15,23,42,0.85);
        }

        .load-more-btn:hover{
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15,23,42,1);
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="main-container">

        <!-- =========================
             BANNER SLIDER
        ========================== -->
        <div class="banner-wrap">
            <div class="banner-shell">
                <div class="banner-container">
                    <?php if (count($banners) > 0): ?>
                        <div class="banner-slider" id="bannerSlider">
                            <?php foreach ($banners as $b): ?>
                                <div class="banner-slide">
                                    <?php if (!empty($b['link_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($b['link_url']); ?>">
                                    <?php endif; ?>

                                    <img src="<?php echo htmlspecialchars($b['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($b['title']); ?>">

                                    <?php if (!empty($b['link_url'])): ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="banner-slider">
                            <div class="banner-slide">
                                <img src="<?php echo BASE_URL; ?>assets/img/banner-default.jpg" alt="Banner">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="banner-dots" id="bannerDots"></div>
            </div>
        </div>

        <!-- =========================
             POPULER SEKARANG
        ========================== -->
        <section class="section-popular">
            <div class="section-popular-title">
                <span class="icon">🔥</span>
                <span>BEST SELLER</span>
            </div>
            <div class="section-popular-sub">
                Here are some of the best-selling products right now.
            </div>

            <div class="popular-grid">
                <?php foreach ($popularGames as $g): ?>
                    <a href="<?php echo htmlspecialchars($g['link']); ?>" class="popular-card">
                        <div class="popular-card-icon">
                            <img src="<?php echo htmlspecialchars($g['image']); ?>" alt="<?php echo htmlspecialchars($g['name']); ?>">
                        </div>
                        <div class="popular-card-text">
                            <div class="title"><?php echo htmlspecialchars($g['name']); ?></div>
                            <div class="subtitle"><?php echo htmlspecialchars($g['publisher']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- =========================
             TAB CATEGORY (DARI DB)
        ========================== -->
        <div class="category-tabs-wrap">
            <div class="category-arrow" onclick="scrollTabs(-1)">&lt;</div>
            <div id="categoryTabs" class="category-tabs">
                <div class="category-pill active" data-cat="all">Semua</div>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-pill"
                         data-cat="<?php echo htmlspecialchars(cat_slug($cat)); ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="category-arrow" onclick="scrollTabs(1)">&gt;</div>
        </div>

        <!-- =========================
             GAME GRID
        ========================== -->
        <section class="games-section">
            <div class="games-grid" id="gamesGrid">
                <?php foreach ($games as $game): ?>
                    <?php
                        $link = !empty($game['path'])
                            ? BASE_URL . ltrim($game['path'], '/')
                            : '#';
                        $catSlug = cat_slug($game['category'] ?? '');
                    ?>
                    <a href="<?php echo htmlspecialchars($link); ?>"
                       class="game-card"
                       data-cat="<?php echo htmlspecialchars($catSlug); ?>">
                        <div class="game-card-img">
                            <img src="<?php echo htmlspecialchars($game['icon']); ?>"
                                 alt="<?php echo htmlspecialchars($game['name']); ?>">

                            <div class="game-card-overlay">
                                <div class="overlay-title">
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </div>
                                <div class="overlay-sub">
                                    <?php echo htmlspecialchars($game['publisher'] ?: ''); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (count($games) > 12): ?>
                <div class="load-more-wrap">
                    <button type="button" id="loadMoreGames" class="load-more-btn">
                        Show more
                    </button>
                </div>
            <?php endif; ?>
        </section>

    </div><!-- /.main-container -->

    <?php include '../include/footer.php'; ?>
</div><!-- /.page-wrap -->

<script>
function scrollTabs(direction) {
    const el = document.getElementById('categoryTabs');
    if (!el) return;
    const amount = 120 * direction;
    el.scrollBy({ left: amount, behavior: 'smooth' });
}

/* =========================
   BANNER SLIDER – AUTO + SWIPE
========================= */
(function () {
    const slider = document.getElementById('bannerSlider');
    const dotsContainer = document.getElementById('bannerDots');
    if (!slider) return;

    const slides = slider.querySelectorAll('.banner-slide');
    const total  = slides.length;
    if (total === 0) return;

    let current = 0;
    let autoTimer = null;
    const interval = 5000;

    const dots = [];
    if (dotsContainer) {
        for (let i = 0; i < total; i++) {
            const d = document.createElement('div');
            if (i === 0) d.classList.add('active');
            dotsContainer.appendChild(d);
            dots.push(d);
        }
    }

    function updateDots() {
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function goTo(index) {
        current = (index + total) % total;
        slider.style.transform = 'translateX(' + (-current * 100) + '%)';
        updateDots();
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAuto() {
        if (autoTimer) clearInterval(autoTimer);
        autoTimer = setInterval(next, interval);
    }

    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    slider.addEventListener('touchstart', function (e) {
        if (!e.touches.length) return;
        startX = e.touches[0].clientX;
        currentX = startX;
        isDragging = true;
        slider.style.transition = 'none';
        if (autoTimer) clearInterval(autoTimer);
    }, { passive: true });

    slider.addEventListener('touchmove', function (e) {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        const diff = currentX - startX;
        const percent = diff / slider.offsetWidth * 100;
        slider.style.transform = 'translateX(' + (-current * 100 + percent) + '%)';
    }, { passive: true });

    slider.addEventListener('touchend', function () {
        if (!isDragging) return;
        isDragging = false;
        slider.style.transition = 'transform 0.4s ease';
        const diff = currentX - startX;
        const threshold = slider.offsetWidth * 0.2;

        if (Math.abs(diff) > threshold) {
            if (diff < 0) next(); else prev();
        } else {
            goTo(current);
        }
        startAuto();
    });

    startAuto();
})();

/* =========================
   GAME CARD – OVERLAY + LOAD MORE + CATEGORY FILTER
========================= */
document.addEventListener('DOMContentLoaded', function () {
    const gamesGrid   = document.getElementById('gamesGrid');
    const allCards    = Array.from(gamesGrid.querySelectorAll('.game-card'));
    const loadMoreBtn = document.getElementById('loadMoreGames');
    const perPage     = 12;
    let shown         = 0;
    let filteredCards = allCards.slice();

    allCards.forEach(function(card){
        card.addEventListener('touchstart', function () {
            card.classList.add('show-overlay');
        }, {passive: true});

        card.addEventListener('touchend', function () {
            setTimeout(function(){
                card.classList.remove('show-overlay');
            }, 200);
        }, {passive: true});
    });

    function resetCardsDisplay() {
        allCards.forEach(card => {
            card.style.display = 'none';
            card.classList.remove('visible');
            card.style.animationDelay = '0s';
        });
    }

    function updateLoadMoreVisibility() {
        if (!loadMoreBtn) return;
        if (shown >= filteredCards.length || filteredCards.length <= perPage) {
            loadMoreBtn.style.display = (filteredCards.length > perPage) ? 'inline-flex' : 'none';
        } else {
            loadMoreBtn.style.display = 'inline-flex';
        }
    }

    function showBatch() {
        const next = filteredCards.slice(shown, shown + perPage);
        next.forEach((card, index) => {
            card.style.display = 'block';

            card.classList.remove('visible');
            void card.offsetWidth;

            card.style.animationDelay = (index * 0.03) + 's';
            card.classList.add('visible');
        });
        shown += next.length;
        updateLoadMoreVisibility();
    }

    if (filteredCards.length > 0) {
        resetCardsDisplay();
        shown = 0;
        showBatch();
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            showBatch();
        });
    }

    const catPills = document.querySelectorAll('.category-pill');
    catPills.forEach(function(pill){
        pill.addEventListener('click', function(){
            const cat = this.getAttribute('data-cat');

            catPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            filteredCards = allCards.filter(card => {
                const cardCat = card.getAttribute('data-cat') || '';
                if (cat === 'all') return true;
                return cardCat === cat;
            });

            resetCardsDisplay();
            shown = 0;

            if (filteredCards.length > 0) {
                showBatch();
            } else {
                updateLoadMoreVisibility();
            }
        });
    });
});
</script>

</body>
</html>