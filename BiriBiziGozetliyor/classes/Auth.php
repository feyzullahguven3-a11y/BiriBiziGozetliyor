<?php

class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function girisYap($email, $sifre) {
        $stmt = $this->db->prepare("SELECT * FROM personeller WHERE email = ? AND durum = 'aktif'");
        $stmt->execute([$email]);
        $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && password_verify($sifre, $kullanici['sifre_hash'])) {
            // Şifre doğruysa oturum değişkenlerini ayarla
            $_SESSION['personel_id'] = $kullanici['id'];
            $_SESSION['rol'] = $kullanici['rol'];
            $_SESSION['ad_soyad'] = $kullanici['ad_soyad'];
            
            // 🔥 GÜVENLİ DOSYA YOLU: __DIR__ ile çökme engellendi
            require_once __DIR__ . '/Gozetmen.php'; 
            $gozetmen = new Gozetmen($this->db);
            $gozetmen->logTut($kullanici['id'], 'sistem_giris', $kullanici['ad_soyad'] . ' sisteme giriş yaptı.');
            
            return true;
        }
        return false;
    }

    public function oturumKontrol() {
        if (!isset($_SESSION['personel_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    public function yetkiKontrol($izin_verilen_roller = []) {
        $this->oturumKontrol(); // Önce sisteme girmiş mi diye bakar
        
        if (!empty($izin_verilen_roller) && !in_array($_SESSION['rol'], $izin_verilen_roller)) {
            header("Location: dashboard.php?hata=yetkisiz_erisim");
            exit;
        }
    }
}
?>