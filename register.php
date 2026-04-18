<?php
// ===== DEBUG: TUNJUK SEMUA ERROR (boleh padam bila dah stabil) =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$fatalError = '';
$error = '';

try {
    require_once '../config/app.php';
    require_once '../config/db.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kalau dah login → balik home
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Guna isset supaya tak trigger notice
        $name        = isset($_POST['name']) ? trim($_POST['name']) : '';
        $username    = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email       = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phoneCode   = isset($_POST['phone_code']) ? trim($_POST['phone_code']) : '+60';
        $phoneNumber = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $password    = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm     = isset($_POST['confirm']) ? $_POST['confirm'] : '';

        if ($name === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
            $error = 'Sila lengkapkan semua maklumat wajib.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email tidak sah.';
        } elseif ($password !== $confirm) {
            $error = 'Kata laluan tidak sama.';
        } else {
            // Gabung phone (optional)
            $phone = '';
            if ($phoneNumber !== '') {
                $phone = $phoneCode . $phoneNumber;
            }

            // ===== PENTING: pastikan table users ada kolum:
            // id, name, username, email, phone, password, role
            // =================================================

            // Check duplicate email/username
            $checkSql = "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1";
            $check = $conn->prepare($checkSql);
            $check->bind_param('ss', $email, $username);
            $check->execute();
            $res = $check->get_result();

            if ($res && $res->num_rows > 0) {
                $error = 'Email atau Username sudah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insSql = "INSERT INTO users (name, username, email, phone, password, role)
                           VALUES (?, ?, ?, ?, ?, 'public')";
                $stmt = $conn->prepare($insSql);
                $stmt->bind_param('sssss', $name, $username, $email, $phone, $hash);

                if ($stmt->execute()) {
                    // Berjaya → auto login & redirect
                    $_SESSION['user_id']  = $stmt->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role']     = 'public';

                    header('Location: ' . BASE_URL);
                    exit;
                } else {
                    // Tak akan sampai sini kalau mysqli_report aktif,
                    // tapi just in case
                    $error = 'Ralat semasa mendaftar: ' . $stmt->error;
                }
            }
        }
    }

} catch (Exception $e) {
    // Apa-apa error DB / lain-lain jatuh sini
    $fatalError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akaun - <?php echo isset($APP_NAME) ? APP_NAME : 'MinzzShop'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --text-main: #ffffff;
            --text-muted: #9ca3af;
            --accent-blue: #2563eb;
        }

        *{ box-sizing: border-box; }

        body{
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #020617;
            color: var(--text-main);
        }

        a{ color: #60a5fa; text-decoration: none; }

        .page-wrap{
            min-height: 100vh;
        }

        .main-container{
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 14px 40px;
        }

        .auth-wrap{
            max-width: 380px;
            margin: 70px auto 0;
            padding: 0 12px;
        }

        .auth-title{
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .auth-sub{
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .auth-group{
            margin-bottom: 16px;
        }

        .auth-label{
            font-size: 13px;
            margin-bottom: 5px;
        }

        .auth-input{
            width: 100%;
            padding: 11px 13px;
            border-radius: 8px;
            border: 1px solid #1e293b;
            background: #0f172a;
            color: var(--text-main);
            font-size: 14px;
        }

        .auth-input:focus{
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37,99,235,0.5);
        }

        .phone-row{
            display: grid;
            grid-template-columns: 110px minmax(0,1fr);
            gap: 8px;
        }

        .phone-code-select{
            padding: 11px 10px;
            border-radius: 8px;
            border: 1px solid #1e293b;
            background: #0f172a;
            color: var(--text-main);
            font-size: 14px;
        }

        .phone-code-select:focus{
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37,99,235,0.5);
        }

        .auth-submit-btn{
            width: 100%;
            padding: 11px 0;
            border-radius: 16px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: var(--accent-blue);
            cursor: pointer;
            margin-top: 4px;
        }

        .auth-submit-btn:active{
            transform: translateY(1px);
        }

        .auth-error,
        .fatal-error{
            background: rgba(248,113,113,0.12);
            border: 1px solid rgba(248,113,113,0.5);
            color: #fecaca;
            padding: 10px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 14px;
            white-space: pre-wrap;
        }

        .auth-bottom-text{
            margin-top: 14px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
        }

        @media(max-width:480px){
            .auth-wrap{ margin-top: 60px; }
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="main-container">
        <div class="auth-wrap">

            <div class="auth-title">Register Account</div>
            <div class="auth-sub">Create your MinzzShop account now.</div>

            <?php if ($fatalError): ?>
                <div class="fatal-error">
                    <strong>Fatal error:</strong><br>
                    <?php echo htmlspecialchars($fatalError); ?>
                </div>
            <?php endif; ?>

            <?php if ($error && !$fatalError): ?>
                <div class="auth-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="auth-group">
                    <div class="auth-label">Full Name</div>
                    <input type="text" name="name" class="auth-input"
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="auth-group">
                    <div class="auth-label">Username</div>
                    <input type="text" name="username" class="auth-input"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="auth-group">
                    <div class="auth-label">Email</div>
                    <input type="email" name="email" class="auth-input"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="auth-group">
                    <div class="auth-label">No. Telefon (optional)</div>
                    <div class="phone-row">
                        <select name="phone_code" class="phone-code-select">
                            <?php
                            $selectedCode = isset($_POST['phone_code']) ? $_POST['phone_code'] : '+60';
                            $codes = [
                                '+60'  => 'MY +60',
                                '+62'  => 'ID +62',
                                '+65'  => 'SG +65',
                                '+66'  => 'TH +66',
                                '+673' => 'BN +673',
                            ];
                            foreach ($codes as $code => $label) {
                                $sel = ($code === $selectedCode) ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($code).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
                            }
                            ?>
                        </select>

                        <input type="text" name="phone" class="auth-input" placeholder="123456789"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="auth-group">
                    <div class="auth-label">Password</div>
                    <input type="password" name="password" class="auth-input">
                </div>

                <div class="auth-group">
                    <div class="auth-label">Repeat Password</div>
                    <input type="password" name="confirm" class="auth-input">
                </div>

                <button type="submit" class="auth-submit-btn">Register</button>
            </form>

            <div class="auth-bottom-text">
                Sudah mempunyai akaun?
                <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>