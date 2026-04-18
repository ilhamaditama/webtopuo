<?php
require_once '../config/app.php';
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login → redirect
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Sila masukkan email/username dan kata laluan.';
    } else {
        // Cari user berdasarkan email / username / phone
        $sql = "SELECT id, name, username, email, phone, password, role 
                FROM users 
                WHERE email = ? OR username = ? OR phone = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $identifier, $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $hash = $row['password'];
            $ok = false;

            if (password_verify($password, $hash)) {
                $ok = true;
            } elseif ($password === $hash) { // fallback kalau DB masih plaintext
                $ok = true;
            }

            if ($ok) {
                // Set session
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'] ?: $row['name'] ?: $row['email'];
                $_SESSION['role']     = $row['role'];

                // Redirect ikut role
                if ($row['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/index.php');
                } else {
                    header('Location: ' . BASE_URL);
                }
                exit;
            } else {
                $error = 'Kata laluan salah.';
            }

        } else {
            $error = 'Akaun tidak dijumpai.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Masuk - <?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg-main: #020617;
            --text-main: #ffffff;
            --text-muted: #9ca3af;
            --accent-blue: #2563eb;
        }

        body{
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #020617;
            color: var(--text-main);
        }

        .page-wrap{
            min-height: 100vh;
        }

        .main-container{
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 14px 40px;
        }

        /* SIMPLE LOGIN FORM */
        .auth-wrap{
            max-width: 520px;
            margin: 80px auto 0;
            padding: 0 6px;
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
            padding: 12px 14px;
            border-radius: 10px;
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

        .auth-submit-btn{
            width: 100%;
            padding: 12px;
            border-radius: 999px;
            border: none;
            background: var(--accent-blue);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 4px;
        }

        .auth-submit-btn:active{
            transform: translateY(1px);
        }

        .auth-error{
            background: rgba(248,113,113,0.12);
            border: 1px solid rgba(248,113,113,0.4);
            color: #fecaca;
            padding: 10px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .auth-bottom-text{
            text-align: center;
            margin-top: 14px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .auth-bottom-text a{
            color: #93c5fd;
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

            <div class="auth-title">Login</div>
            <div class="auth-sub">Please enter your account information.</div>

            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="auth-group">
                    <div class="auth-label">Email / Username / No. Telefon</div>
                    <input type="text" name="identifier" class="auth-input"
                           placeholder="contoh: email@domain.com"
                           value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
                </div>

                <div class="auth-group">
                    <div class="auth-label">Password</div>
                    <input type="password" name="password" class="auth-input" placeholder="••••••••">
                </div>

                <button type="submit" class="auth-submit-btn">Login</button>
            </form>

            <div class="auth-bottom-text">
                Belum ada akaun?
                <a href="<?php echo BASE_URL; ?>register.php">Register Now</a>
            </div>

        </div>
    </div>

</div>

</body>
</html>