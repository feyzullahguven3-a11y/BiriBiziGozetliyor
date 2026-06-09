<?php
require_once 'config/db.php';
$mesaj = "";
$gecerli_token_mi = false;
$personel_id = 0;
$talep_id = 0;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $db->prepare("SELECT id, personel_id FROM sifre_talepleri WHERE token = ? AND durum = 'onaylandi'");
    $stmt->execute([$token]);
    $talep = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($talep) {
        $gecerli_token_mi = true;
        $personel_id = $talep['personel_id'];
        $talep_id = $talep['id'];
    } else {
        $mesaj = "<div class='alert error'>Geçersiz veya daha önce kullanılmış bir şifre sıfırlama bağlantısı!</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sifre_yenile'])) {
    $yeni_sifre = $_POST['yeni_sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    
    if ($yeni_sifre === $sifre_tekrar) {
        $personel_id = $_POST['personel_id'];
        $talep_id = $_POST['talep_id'];
        
        $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        
        $db->prepare("UPDATE personeller SET sifre_hash = ? WHERE id = ?")->execute([$sifre_hash, $personel_id]);
        
        $db->prepare("UPDATE sifre_talepleri SET durum = 'kullanildi' WHERE id = ?")->execute([$talep_id]);
        
        $gecerli_token_mi = false;
        $mesaj = "<div class='alert success'>Şifreniz başarıyla değiştirildi! Artık yeni şifrenizle giriş yapabilirsiniz.</div>";
    } else {
        $mesaj = "<div class='alert error'>Şifreler birbiriyle eşleşmiyor! Lütfen tekrar deneyin.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Şifre Belirle</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 10px;}
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 15px; color: white;}
        .error { background: #ef4444; }
        .success { background: #10b981; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔑 Yeni Şifre Oluştur</h2>
        <?php echo $mesaj; ?>
        
        <?php if($gecerli_token_mi): ?>
        <form method="POST" action="">
            <input type="hidden" name="personel_id" value="<?php echo $personel_id; ?>">
            <input type="hidden" name="talep_id" value="<?php echo $talep_id; ?>">
            
            <input type="password" name="yeni_sifre" required placeholder="Yeni Şifreniz (Min 4 karakter)" minlength="4">
            <input type="password" name="sifre_tekrar" required placeholder="Yeni Şifrenizi Tekrar Girin">
            
            <button type="submit" name="sifre_yenile">Şifremi Güncelle</button>
        </form>
        <?php else: ?>
            <a href="login.php" style="display:inline-block; margin-top:20px; color:#4f46e5; text-decoration:none; font-weight:bold;">Sisteme Giriş Yap</a>
        <?php endif; ?>
    </div>
</body>
</html>