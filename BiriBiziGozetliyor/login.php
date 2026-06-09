<?php
// login.php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$hata_mesaji = "";

if (isset($_SESSION['personel_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $sifre = trim($_POST['sifre']);

    if ($auth->girisYap($email, $sifre)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $hata_mesaji = "E-posta veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Gözetim Merkezi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --border-color: #e5e7eb;
            --danger-color: #ef4444;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #111827;
                --card-bg: #1f2937;
                --text-main: #f9fafb;
                --text-muted: #9ca3af;
                --border-color: #374151;
            }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
        }

        .login-container {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .logo-area { text-align: center; margin-bottom: 30px; }
        .logo-area i { font-size: 48px; color: var(--primary-color); margin-bottom: 10px; }
        .logo-area h1 { font-size: 24px; font-weight: 700; }
        .logo-area p { color: var(--text-muted); font-size: 14px; margin-top: 5px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .input-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: var(--text-main);
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }
        .input-wrapper input:focus { border-color: var(--primary-color); }

        .btn-login {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover { background-color: var(--primary-hover); }

        .alert { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); padding: 12px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: 600; border: 1px solid rgba(239, 68, 68, 0.3); }
        
        .forgot-link {
            display: block;
            text-align: right;
            margin-top: 8px;
            font-size: 13px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .forgot-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-area">
            <i class="fa-solid fa-eye"></i>
            <h1>Gözetim Merkezi</h1>
            <p>Sisteme erişmek için giriş yapın</p>
        </div>

        <?php if(!empty($hata_mesaji)): ?>
            <div class="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $hata_mesaji; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="ornek@firma.com">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 10px;">
                <label for="sifre">Şifre</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="sifre" name="sifre" required placeholder="••••••••">
                </div>
                <a href="sifremi_unuttum.php" class="forgot-link">Şifremi Unuttum?</a>
            </div>

            <button type="submit" class="btn-login">Sisteme Giriş Yap</button>
        </form>
    </div>

</body>
</html>