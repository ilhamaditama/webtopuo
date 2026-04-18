<?php
// public/check-region.php

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
    <title>Check Region MLBB - <?php echo APP_NAME; ?></title>
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

        @media(min-width:900px){
            .main-container{
                margin-left: 240px; /* ikut admin layout, tapi header public biasa tak ada sidebar fixed */
            }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.96); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* HERO CARD */
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
            padding:14px 14px 16px;
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

        .form-grid-2{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:10px;
        }
        @media(max-width:520px){
            .form-grid-2{ grid-template-columns:1fr; }
        }

        .form-row{
            display:flex;
            flex-direction:column;
            gap:4px;
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
            margin-top:8px;
            font-size:11px;
            color:var(--danger);
        }

        /* RESULT MODAL */
        @keyframes modalFadeIn {
            from { opacity:0; }
            to   { opacity:1; }
        }
        @keyframes modalSlideUp {
            from { transform: translateY(26px) scale(0.96); opacity:0; }
            to   { transform: translateY(0) scale(1); opacity:1; }
        }

        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.9);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:300;
        }
        .modal-backdrop.show{
            display:flex;
            animation:modalFadeIn .2s ease-out;
        }

        .modal-card{
            width:92%;
            max-width:420px;
            border-radius:24px;
            background:linear-gradient(145deg,#020617,#020b1f);
            border:1px solid rgba(148,163,184,0.4);
            box-shadow:0 26px 60px rgba(0,0,0,0.95);
            padding:16px 16px 14px;
            color:#e5e7eb;
            animation:modalSlideUp .22s ease-out;
        }

        .modal-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            margin-bottom:8px;
        }
        .modal-title{
            font-size:16px;
            font-weight:700;
        }
        .modal-close{
            border:none;
            background:transparent;
            color:#9ca3af;
            font-size:20px;
            cursor:pointer;
        }

        .modal-tagline{
            font-size:11px;
            color:var(--text-muted);
            margin-bottom:10px;
        }

        .result-main{
            border-radius:18px;
            background:#020617;
            border:1px solid rgba(31,41,55,0.9);
            padding:10px 12px;
        }

        .result-row{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:8px;
            font-size:13px;
            margin-bottom:6px;
        }
        .result-label{
            color:#9ca3af;
        }
        .result-value{
            font-weight:500;
            text-align:right;
        }

        .badge-country{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:3px 10px;
            border-radius:999px;
            background:rgba(15,23,42,0.9);
            border:1px solid rgba(148,163,184,0.5);
            font-size:11px;
            margin-top:4px;
        }

        .section-title-small{
            margin-top:10px;
            font-size:12px;
            font-weight:600;
            color:#9ca3af;
            margin-bottom:4px;
        }

        .pkg-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:6px;
            margin-top:4px;
            font-size:11px;
        }
        @media(max-width:420px){
            .pkg-grid{ grid-template-columns:1fr; }
        }

        .pkg-card{
            border-radius:10px;
            background:#020617;
            border:1px solid rgba(51,65,85,0.9);
            padding:6px 8px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:6px;
        }
        .pkg-name{
            font-weight:500;
        }
        .pkg-status{
            font-size:11px;
        }

        .modal-footer-text{
            margin-top:8px;
            font-size:10px;
            color:#64748b;
            text-align:right;
        }
        .modal-footer-text span{
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
                        <img src="<?php echo htmlspecialchars($logo ?? ''); ?>" alt="Logo">
                    </div>
                    <div>
                        <div class="hero-title">Check Region MLBB</div>
                        <div class="hero-sub">
                            Masukkan <strong>User ID</strong> &amp; <strong>Zone ID</strong> Mobile Legends
                            untuk semak region & status first topup.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FORM -->
        <section class="form-card">
            <div class="form-title">Cari Akaun</div>
            <div class="form-sub">
                Pastikan User ID &amp; Zone ID tepat seperti dalam game (contoh: <code>123456789</code> &amp; <code>1234</code>).
            </div>

            <form id="checkForm">
                <div class="form-grid-2">
                    <div class="form-row">
                        <label for="userId">User ID</label>
                        <input type="text" id="userId" name="userId" class="form-input"
                               placeholder="Contoh: 1537856515">
                    </div>
                    <div class="form-row">
                        <label for="zoneId">Zone ID</label>
                        <input type="text" id="zoneId" name="zoneId" class="form-input"
                               placeholder="Contoh: 16442">
                    </div>
                </div>

                <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                    <div id="errorText" class="error-text" style="display:none;"></div>
                    <button type="submit" id="submitBtn" class="btn-primary">
                        <div class="btn-spinner" id="btnSpinner" style="display:none;"></div>
                        <span id="btnText">Check Region Sekarang</span>
                    </button>
                </div>
            </form>
        </section>

    </div><!-- /.main-container -->

    <?php include '../include/footer.php'; ?>
</div><!-- /.page-wrap -->

<!-- RESULT MODAL -->
<div class="modal-backdrop" id="resultModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Hasil Semakan</div>
            <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
        </div>
        <div class="modal-tagline">
            Maklumat akaun & status first topup MLBB.
        </div>

        <div class="result-main">
            <div class="result-row">
                <div class="result-label">Username</div>
                <div class="result-value" id="resUsername">-</div>
            </div>
            <div class="result-row">
                <div class="result-label">Negara</div>
                <div class="result-value">
                    <div id="resCountry">-</div>
                    <div class="badge-country" id="resCountryBadge" style="display:none;">
                        <span id="resCountryFlag">🇲🇾</span>
                        <span id="resCountryText">Malaysia</span>
                    </div>
                </div>
            </div>

            <div class="section-title-small">First Topup (Pakej Utama)</div>
            <div class="pkg-grid" id="resPackages"></div>

            <div class="section-title-small">First Topup (Pakej Tambahan)</div>
            <div class="pkg-grid" id="resPackages2"></div>
        </div>

        <div class="modal-footer-text">
            Fetch by <span>Gamevia</span>
        </div>
    </div>
</div>

<script>
const form       = document.getElementById('checkForm');
const userIdEl   = document.getElementById('userId');
const zoneIdEl   = document.getElementById('zoneId');
const errorText  = document.getElementById('errorText');
const submitBtn  = document.getElementById('submitBtn');
const btnSpinner = document.getElementById('btnSpinner');
const btnText    = document.getElementById('btnText');

const modal          = document.getElementById('resultModal');
const modalCloseBtn  = document.getElementById('modalCloseBtn');

const resUsername    = document.getElementById('resUsername');
const resCountry     = document.getElementById('resCountry');
const resCountryBadge= document.getElementById('resCountryBadge');
const resCountryFlag = document.getElementById('resCountryFlag');
const resCountryText = document.getElementById('resCountryText');
const resPackages    = document.getElementById('resPackages');
const resPackages2   = document.getElementById('resPackages2');

function showError(msg){
    errorText.textContent = msg;
    errorText.style.display = 'block';
}
function clearError(){
    errorText.textContent = '';
    errorText.style.display = 'none';
}

function setLoading(isLoading){
    if (isLoading){
        submitBtn.disabled = true;
        btnSpinner.style.display = 'block';
        btnText.textContent = 'Memproses...';
    } else {
        submitBtn.disabled = false;
        btnSpinner.style.display = 'none';
        btnText.textContent = 'Check Region Sekarang';
    }
}

function openModal(){
    modal.classList.add('show');
}
function closeModal(){
    modal.classList.remove('show');
}

modalCloseBtn.addEventListener('click', closeModal);
modal.addEventListener('click', function(e){
    if (e.target === modal) closeModal();
});

form.addEventListener('submit', function(e){
    e.preventDefault();
    clearError();

    const userId = (userIdEl.value || '').trim();
    const zoneId = (zoneIdEl.value || '').trim();

    if (!userId){
        showError('Sila masukkan User ID.');
        userIdEl.focus();
        return;
    }
    if (!zoneId){
        showError('Sila masukkan Zone ID.');
        zoneIdEl.focus();
        return;
    }

    setLoading(true);

    const url = `https://deoberon-api.vercel.app/stalk/mlbb-first?apikey=nalli&userId=${encodeURIComponent(userId)}&zoneId=${encodeURIComponent(zoneId)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            setLoading(false);

            if (!data || data.status !== true){
                showError(data && data.message ? data.message : 'Akaun tidak ditemui atau API gagal.');
                return;
            }

            // isi data ke modal (JANGAN tunjuk creator)
            resUsername.textContent = data.username || '-';

            const countryRaw = data.country || '';
            resCountry.textContent = countryRaw || '-';

            // cuba pecahkan negara & flag
            // contoh "Malaysia 🇲🇾"
            if (countryRaw){
                const parts = countryRaw.split(' ');
                const lastPart = parts[parts.length - 1];
                if (lastPart.length <= 4){ // anggap ini flag
                    resCountryFlag.textContent = lastPart;
                    resCountryText.textContent = parts.slice(0, -1).join(' ') || countryRaw;
                } else {
                    resCountryFlag.textContent = '🌎';
                    resCountryText.textContent = countryRaw;
                }
                resCountryBadge.style.display = 'inline-flex';
            } else {
                resCountryBadge.style.display = 'none';
            }

            // pakej
            resPackages.innerHTML  = '';
            resPackages2.innerHTML = '';

            if (data.firstTopup && Array.isArray(data.firstTopup.packages)){
                data.firstTopup.packages.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'pkg-card';
                    div.innerHTML = `
                        <div class="pkg-name">${p.name || '-'}</div>
                        <div class="pkg-status">${p.status || ''}</div>
                    `;
                    resPackages.appendChild(div);
                });
            }

            if (data.firstTopup && Array.isArray(data.firstTopup.packages2)){
                data.firstTopup.packages2.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'pkg-card';
                    div.innerHTML = `
                        <div class="pkg-name">${p.name || '-'}</div>
                        <div class="pkg-status">${p.status || ''}</div>
                    `;
                    resPackages2.appendChild(div);
                });
            }

            openModal();
        })
        .catch(err => {
            console.error(err);
            setLoading(false);
            showError('Ralat sambungan ke server. Cuba lagi sekejap lagi.');
        });
});
</script>

</body>
</html>