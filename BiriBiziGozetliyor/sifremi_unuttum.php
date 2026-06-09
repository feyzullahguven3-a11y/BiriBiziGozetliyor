<?php
session_start();
require_once 'config/db.php';

$mesaj = "";
$adim = 1; 
$aktif_email = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    
    if (isset($_POST['talep_kontrol'])) {
        $email = trim($_POST['email']);
        $aktif_email = $email;

 
        $stmt = $db->prepare("SELECT id, ad_soyad FROM personeller WHERE email = ? AND durum = 'aktif'");
        $stmt->execute([$email]);
        $personel = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($personel) {
            $personel_id = $personel['id'];
            

            $t_sorgu = $db->prepare("SELECT * FROM sifre_talepleri WHERE personel_id = ? ORDER BY id DESC LIMIT 1");
            $t_sorgu->execute([$personel_id]);
            $talep = $t_sorgu->fetch(PDO::FETCH_ASSOC);

            if (!$talep || $talep['durum'] == 'tamamlandi') {

                $db->prepare("INSERT INTO sifre_talepleri (personel_id, durum) VALUES (?, 'bekliyor')")->execute([$personel_id]);
                $mesaj = "<div class='alert' style='background:#f59e0b; color:white;'>Talebiniz yöneticiye iletildi. Lütfen yöneticiniz onayladıktan sonra e-postanızı tekrar buraya girerek kontrol ediniz.</div>";
            } 
            elseif ($talep['durum'] == 'bekliyor') {

                $mesaj = "<div class='alert' style='background:#3b82f6; color:white;'>Talebiniz şu anda <strong>onay bekliyor</strong>. Lütfen daha sonra e-postanızı girerek tekrar kontrol ediniz.</div>";
            } 
            elseif ($talep['durum'] == 'onaylandi') {

                $adim = 2; 
                $mesaj = "<div class='alert' style='background:#10b981; color:white;'>Talebiniz <strong>onaylandı!</strong> Lütfen sistem için yeni şifrenizi belirleyin.</div>";
            }
        } else {
            $mesaj = "<div class='alert' style='background:#ef4444; color:white;'>Bu e-posta adresine ait aktif bir hesap bulunamadı!</div>";
        }
    }
    

    elseif (isset($_POST['sifre_yenile'])) {
        $email = $_POST['email'];
        $yeni_sifre = $_POST['yeni_sifre'];
        $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

 
        $p_sorgu = $db->prepare("SELECT id FROM personeller WHERE email = ?");
        $p_sorgu->execute([$email]);
        $personel_id = $p_sorgu->fetchColumn();


        $db->prepare("UPDATE personeller SET sifre_hash = ? WHERE id = ?")->execute([$sifre_hash, $personel_id]);
        

        $db->prepare("UPDATE sifre_talepleri SET durum = 'tamamlandi' WHERE personel_id = ? AND durum = 'onaylandi'")->execute([$personel_id]);

        $mesaj = "<div class='alert' style='background:#10b981; color:white;'>🎉 Harika! Şifreniz başarıyla yenilendi.<br><br><a href='login.php' style='color:white; font-weight:bold; text-decoration:underline;'>Giriş Yapmak İçin Tıklayın</a></div>";
        $adim = 3; 
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum | Gözetim Merkezi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px;}
        button { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.3s;}
        button:hover { background: #4338ca; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 15px; line-height: 1.5; font-size: 14px;}
        a.link-btn { color: #6b7280; text-decoration: none; font-size: 14px; display: inline-block; margin-top: 15px; }
        a.link-btn:hover { color: #4f46e5; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔒 Şifre Sıfırlama</h2>
        
        <?php echo $mesaj; ?>

        <?php if($adim == 1): ?>
            <p style="color:#666; font-size:14px;">Talebinizi iletmek veya onay durumunu sorgulamak için e-posta adresinizi girin.</p>
            <form method="POST">
                <input type="email" name="email" required placeholder="E-posta Adresiniz" value="<?php echo htmlspecialchars($aktif_email); ?>">
                <button type="submit" name="talep_kontrol">Durumu Sorgula / Talep Aç</button>
            </form>
            <a href="login.php" class="link-btn">← Giriş Ekranına Dön</a>

        <?php elseif($adim == 2): ?>
            <p style="color:#666; font-size:14px;">Talebiniz onaylandı! Hesabınız için yeni bir şifre belirleyebilirsiniz.</p>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($aktif_email); ?>">
                <input type="password" name="yeni_sifre" required placeholder="Yeni Şifrenizi Girin" autofocus>
                <button type="submit" name="sifre_yenile" style="background: #10b981;">Yeni Şifremi Kaydet</button>
            </form>

        <?php elseif($adim == 3): ?>
            <?php endif; ?>
    </div>
</body>
</html>