<?php
// public/kalkulator-magic-wheel.php

require_once '../config/app.php';
require_once '../config/db.php';

// ambil logo dari site_settings
$settings = $conn->query("SELECT logo_url FROM site_settings LIMIT 1")->fetch_assoc();
$logo = !empty($settings['logo_url'])
    ? $settings['logo_url']
    : (BASE_URL . 'assets/img/logo.png');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Kalkulator Magic Wheel - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FONT: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --bg-section: #020617;
            --card: #020818;
            --border: #1e293b;
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --accent-1: #2563eb;
            --accent-2: #38bdf8;
            --danger: #f97373;
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
            min-height: 100vh;
            background: var(--bg-section);
        }

        .main-container{
            max-width: 960px;
            margin: 0 auto;
            padding: 10px 14px 80px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.96); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* HERO */
        .hero-wrap{
            margin: 10px 0 20px;
            animation: fadeUp .45s ease-out both;
        }

        .hero-card{
            position: relative;
            border-radius: 24px;
            padding: 18px 16px 16px;
            background: radial-gradient(circle at top, #02091c, #020617 60%);
            border: 1px solid rgba(148,163,184,0.35);
            box-shadow: 0 20px 40px rgba(0,0,0,0.9);
            overflow: hidden;
        }

        .hero-card::before{
            content:"";
            position:absolute;
            inset:-80px;
            background:
                radial-gradient(circle at 0 0, rgba(56,189,248,0.18), transparent 55%),
                radial-gradient(circle at 100% 0, rgba(37,99,235,0.16), transparent 50%);
            opacity:0.9;
            pointer-events:none;
        }
        .hero-inner{
            position:relative;
            z-index:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:14px;
            text-align:center;
        }

        .hero-logo{
            width:80px;
            height:80px;
            border-radius:26px;
            border:2px solid rgba(56,189,248,0.8);
            background:#020617;
            overflow:hidden;
            box-shadow:0 18px 40px rgba(0,0,0,0.9);
            animation: fadeInScale .45s ease-out both;
        }
        .hero-logo img{
            width:100%;
            height:100%;
            object-fit:contain;
        }

        .hero-title{
            font-size:20px;
            font-weight:700;
            letter-spacing:0.4px;
        }
        .hero-sub{
            font-size:13px;
            color:var(--text-muted);
        }

        /* FORM CARD */
        .form-card{
            margin-top:18px;
            border-radius:20px;
            background:#020818;
            border:1px solid var(--border);
            padding:14px 14px 18px;
            box-shadow:0 20px 45px rgba(0,0,0,0.9);
            animation: fadeUp .5s ease-out both;
        }

        .form-title{
            font-size:15px;
            font-weight:600;
            margin-bottom:6px;
        }
        .form-sub{
            font-size:12px;
            color:var(--text-muted);
            margin-bottom:10px;
        }

        .form-row{
            display:flex;
            flex-direction:column;
            gap:4px;
            margin-bottom:10px;
        }
        .form-row label{
            font-size:12px;
        }
        .form-input{
            width:100%;
            border-radius:12px;
            border:1px solid #1f2937;
            background:#020617;
            color:#e5e7eb;
            padding:9px 11px;
            font-size:13px;
            outline:none;
        }
        .form-input::placeholder{
            color:#6b7280;
        }

        .helper-text{
            font-size:11px;
            color:var(--text-muted);
        }

        .btn-primary{
            border:none;
            border-radius:999px;
            padding:10px 18px;
            background:linear-gradient(135deg,var(--accent-1),var(--accent-2));
            color:#f9fafb;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            box-shadow:0 10px 26px rgba(15,23,42,0.95);
            display:inline-flex;
            align-items:center;
            gap:8px;
        }

        .btn-primary[disabled]{
            opacity:0.6;
            cursor:not-allowed;
            box-shadow:none;
        }

        .btn-spinner{
            width:16px;
            height:16px;
            border-radius:999px;
            border:2px solid rgba(248,250,252,0.4);
            border-top-color:#e5e7eb;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-text{
            margin-top:6px;
            font-size:11px;
            color:var(--danger);
        }

        /* POPUP RESULT */
        @keyframes popupFadeIn {
            from { opacity:0; }
            to   { opacity:1; }
        }
        @keyframes popupSlideUp {
            from { transform:translateY(26px) scale(0.96); opacity:0; }
            to   { transform:translateY(0) scale(1); opacity:1; }
        }

        .popup-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.9);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:300;
        }
        .popup-backdrop.show{
            display:flex;
            animation: popupFadeIn .2s ease-out;
        }

        .popup-card{
            width:92%;
            max-width:420px;
            border-radius:24px;
            background:linear-gradient(145deg,#020617,#020b1f);
            border:1px solid rgba(148,163,184,0.4);
            box-shadow:0 26px 60px rgba(0,0,0,0.95);
            padding:16px 16px 14px;
            color:#e5e7eb;
            animation: popupSlideUp .22s ease-out;
        }

        .popup-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:8px;
            margin-bottom:8px;
        }
        .popup-title{
            font-size:16px;
            font-weight:700;
        }
        .popup-close{
            border:none;
            background:transparent;
            color:#9ca3af;
            font-size:20px;
            cursor:pointer;
        }

        .popup-body{
            font-size:13px;
            line-height:1.6;
        }

        .popup-body b{
            color:#fbbf24;
        }

        .popup-footer{
            margin-top:10px;
            font-size:11px;
            color:#64748b;
            text-align:right;
        }

        .popup-footer span{
            font-weight:600;
            color:#e5e7eb;
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="main-container">

        <!-- HERO -->
        <section class="hero-wrap">
            <div class="hero-card">
                <div class="hero-inner">
                    <div class="hero-logo">
                        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo">
                    </div>
                    <div>
                        <div class="hero-title">Kalkulator Magic Wheel</div>
                        <div class="hero-sub">
                            Kira anggaran maksimum jumlah <strong>diamond</strong> yang diperlukan
                            untuk dapatkan skin di Magic Wheel berdasarkan draw yang anda sudah buat.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FORM -->
        <section class="form-card">
            <div class="form-title">Masukkan Data Magic Wheel</div>
            <div class="form-sub">
                Isi jumlah <strong>Draw Now</strong> yang sudah dibuat pada Magic Wheel sekarang.
            </div>

            <form id="magicForm">
                <div class="form-row">
                    <label for="drawNow">Jumlah Draw Sekarang</label>
                    <input type="number" min="0" step="1" id="drawNow" name="drawNow"
                           class="form-input" placeholder="Contoh: 120">
                    <div class="helper-text">
                        * Masukkan bilangan draw keseluruhan yang anda sudah spin di Magic Wheel sekarang.
                    </div>
                </div>

                <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                    <div id="errorText" class="error-text" style="display:none;"></div>
                    <button type="submit" class="btn-primary" id="calcBtn">
                        <div class="btn-spinner" id="btnSpinner" style="display:none;"></div>
                        <span id="btnText">Kira Sekarang</span>
                    </button>
                </div>
            </form>
        </section>

    </div><!-- /.main-container -->

    <?php include '../include/footer.php'; ?>
</div><!-- /.page-wrap -->

<!-- POPUP RESULT -->
<div class="popup-backdrop" id="popup">
    <div class="popup-card">
        <div class="popup-header">
            <div class="popup-title">Hasil Kiraan</div>
            <button type="button" class="popup-close" onclick="closePopup()">&times;</button>
        </div>
        <div class="popup-body" id="popupResult">
            <!-- diisi oleh JavaScript -->
        </div>
        <div class="popup-footer">
            Kalkulator oleh <span>Gamevia Tools</span>
        </div>
    </div>
</div>

<script>
// helper: show/hide error bawah input
const errorText  = document.getElementById("errorText");
const calcBtn    = document.getElementById("calcBtn");
const btnSpinner = document.getElementById("btnSpinner");
const btnText    = document.getElementById("btnText");

function setLoading(isLoading){
    if (isLoading){
        calcBtn.disabled = true;
        btnSpinner.style.display = "block";
        btnText.textContent = "Mengira...";
    } else {
        calcBtn.disabled = false;
        btnSpinner.style.display = "none";
        btnText.textContent = "Kira Sekarang";
    }
}

function showError(msg){
    errorText.textContent = msg;
    errorText.style.display = "block";
}
function clearError(){
    errorText.textContent = "";
    errorText.style.display = "none";
}

// ====== FUNCTION KAU (DIPAKAI DALAM EVENT) ======
document.getElementById("magicForm").addEventListener("submit", function(e){
  e.preventDefault();
  clearError();

  const val    = document.getElementById("drawNow").value;
  const total  = parseFloat(val);

  if (isNaN(total) || total < 0){
      showError("Sila masukkan jumlah draw yang sah (nombor positif).");
      return;
  }

  setLoading(true);

  let maximum = 0;
  if(total < 196){
    let remain = 200 - total;
    let count = Math.ceil(remain / 5);
    maximum = count * 270;
  } else if(total <= 200){
    maximum = (200 - total) * 60;
  } else maximum = 0;

  const popupResult = document.getElementById("popupResult");
  popupResult.innerHTML = `
    Anda memerlukan sekitar <b>${maximum}</b> 💎 maksimum
    untuk mendapat skin Magic Wheel (anggaran kasar, boleh kurang daripada ini bergantung kepada nasib anda).
  `;

  setLoading(false);
  document.getElementById("popup").classList.add("show");
});

// close popup
function closePopup(){
    document.getElementById("popup").classList.remove("show");
}

// tutup bila klik luar kad
document.getElementById("popup").addEventListener("click", function(e){
    if (e.target === this){
        closePopup();
    }
});
</script>

</body>
</html>