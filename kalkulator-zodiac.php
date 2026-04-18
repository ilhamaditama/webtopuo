<?php
require_once '../config/app.php';
require_once '../config/db.php';

// ambil logo dari DB
$settings = $conn->query("SELECT logo_url FROM site_settings LIMIT 1")->fetch_assoc();
$logo = !empty($settings['logo_url']) ? $settings['logo_url'] : (BASE_URL . 'assets/img/logo.png');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Kalkulator Zodiac - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- POPPINS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main:#020617;
            --card:#020818;
            --border:#1e293b;
            --text:#ffffff;
            --text-muted:#94a3b8;
            --blue:#2563eb;
            --blue2:#38bdf8;
        }

        *{ box-sizing:border-box; }
        body{
            margin:0;
            font-family:'Poppins',sans-serif;
            background:radial-gradient(circle at top,#0f172a,#020617 65%);
            color:#fff;
        }

        .main-container{
            max-width:960px;
            margin:0 auto;
            padding:12px 14px 80px;
        }

        @keyframes fadeUp{
            from{ opacity:0; transform:translateY(20px); }
            to{ opacity:1; transform:translateY(0); }
        }

        /* HERO */
        .hero-card{
            margin-top:14px;
            border-radius:24px;
            background:linear-gradient(145deg,#020617,#020b1f);
            border:1px solid rgba(148,163,184,0.3);
            padding:18px;
            text-align:center;
            animation:fadeUp .45s ease-out both;
            box-shadow:0 20px 40px rgba(0,0,0,0.85);
        }
        .hero-logo{
            width:85px;
            height:85px;
            border-radius:26px;
            overflow:hidden;
            border:2px solid rgba(56,189,248,0.7);
            margin:0 auto 14px;
        }
        .hero-logo img{
            width:100%; height:100%; object-fit:contain;
        }
        .hero-title{
            font-size:20px;
            font-weight:700;
            margin-bottom:4px;
        }
        .hero-sub{font-size:13px; color:var(--text-muted);}

        /* FORM CARD */
        .form-card{
            margin-top:20px;
            padding:16px;
            border-radius:20px;
            background:#020818;
            border:1px solid var(--border);
            animation:fadeUp .5s ease-out both;
            box-shadow:0 20px 40px rgba(0,0,0,0.9);
        }
        .form-title{
            font-size:15px; font-weight:600; margin-bottom:6px;
        }
        .form-sub{
            font-size:12px; color:var(--text-muted); margin-bottom:12px;
        }
        .form-row{
            margin-bottom:12px; display:flex; flex-direction:column;
        }
        .form-row label{ font-size:12px; margin-bottom:4px; }
        .form-input{
            padding:10px 12px;
            border-radius:12px;
            border:1px solid #1f2937;
            background:#020617;
            color:#fff;
            font-size:14px;
        }

        .btn-primary{
            background:linear-gradient(135deg,var(--blue),var(--blue2));
            border:none;
            padding:10px 18px;
            border-radius:14px;
            font-size:14px;
            font-weight:600;
            color:#fff;
            cursor:pointer;
            width:100%;
            box-shadow:0 10px 24px rgba(0,0,0,0.9);
        }

        /* POPUP */
        .popup-backdrop{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.78);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:999;
        }
        .popup-backdrop.show{ display:flex; animation:fadeIn .2s ease-out; }

        @keyframes fadeIn{
            from{opacity:0;}
            to{opacity:1;}
        }
        @keyframes slideUp{
            from{ transform:translateY(20px) scale(0.96); opacity:0; }
            to{ transform:translateY(0) scale(1); opacity:1; }
        }

        .popup-card{
            width:92%;
            max-width:420px;
            background:#020617;
            border:1px solid #334155;
            border-radius:20px;
            padding:16px;
            animation:slideUp .25s ease-out;
        }
        .popup-title{
            font-size:17px;
            font-weight:700;
            margin-bottom:6px;
        }
        .popup-close{
            float:right;
            font-size:22px;
            cursor:pointer;
            color:#94a3b8;
        }
        .popup-body{
            font-size:14px;
            line-height:1.6;
        }

        .popup-body b{ color:#fbbf24; }

        .popup-footer{
            text-align:right;
            margin-top:10px;
            font-size:11px;
            color:#64748b;
        }

    </style>
</head>
<body>

<?php include '../include/header.php'; ?>
<?php include '../include/sidebar.php'; ?>

<div class="main-container">

    <!-- HERO -->
    <div class="hero-card">
        <div class="hero-logo">
            <img src="<?php echo htmlspecialchars($logo); ?>">
        </div>
        <div class="hero-title">Kalkulator Zodiac</div>
        <div class="hero-sub">
            Kira jumlah anggaran diamond maksimum berdasar Zodiac Point anda.
        </div>
    </div>

    <!-- FORM -->
    <div class="form-card">
        <div class="form-title">Isi Data Zodiac</div>
        <div class="form-sub">Masukkan Zodiac Point semasa (0 - 100).</div>

        <form id="zodiacForm">
            <div class="form-row">
                <label for="currentPoint">Zodiac Point Sekarang</label>
                <input type="number" id="currentPoint" class="form-input" placeholder="Contoh: 45" required>
            </div>

            <button type="submit" class="btn-primary">Kira Sekarang</button>
        </form>
    </div>

</div>

<?php include '../include/footer.php'; ?>

<!-- POPUP -->
<div class="popup-backdrop" id="popup">
    <div class="popup-card">
        <div class="popup-close" onclick="closePopup()">&times;</div>
        <div class="popup-title">Hasil Kiraan</div>
        <div class="popup-body" id="popupResult"></div>
        <div class="popup-footer">Kalkulator oleh <b>Gamevia</b></div>
    </div>
</div>

<script>
// ====== FUNCTION KAU (dipertingkat UI) ======
document.getElementById("zodiacForm").addEventListener("submit", function(e){
    e.preventDefault();

    const point = parseFloat(document.getElementById("currentPoint").value);

    if (isNaN(point) || point < 0 || point > 100){
        alert("Sila masukkan Zodiac Point antara 0 - 100");
        return;
    }

    let maxDiamond = 
        point < 90 
            ? Math.ceil((2000 - point * 20) * 0.85)
            : Math.ceil(2000 - point * 20);

    document.getElementById("popupResult").innerHTML =
        `Anda memerlukan sekitar <b>${maxDiamond}</b> 💎 maksimum 
        untuk mendapatkan skin Zodiac.`;

    document.getElementById("popup").classList.add("show");
});

function closePopup(){
    document.getElementById("popup").classList.remove("show");
}

// klik luar popup tutup
document.getElementById("popup").addEventListener("click", function(e){
    if (e.target === this){ closePopup(); }
});
</script>

</body>
</html>