<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->oturumKontrol();

$gozetmen = new Gozetmen($db);
$mesaj = "";
$benim_id = $_SESSION['personel_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['profil_guncelle'])) {
    $telefon = trim($_POST['telefon']);
    $yeni_sifre = trim($_POST['yeni_sifre']);
    
    $foto_adi = $_POST['eski_foto']; // Varsayılan olarak eski fotoğraf kalır
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $izin_verilenler = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['foto']['type'], $izin_verilenler)) {
            // Fotoğraf ismini benzersiz yap (Örn: 5_16849392.jpg)
            $uzanti = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_adi = $benim_id . "_" . time() . "." . $uzanti;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_adi);
            
            $_SESSION['foto'] = $foto_adi; 
        } else {
            $mesaj = "<div class='alert error'>Sadece JPG, PNG veya GIF yükleyebilirsiniz!</div>";
        }
    }

    try {
        if (!empty($yeni_sifre)) {
            $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $db->prepare("UPDATE personeller SET telefon = ?, sifre_hash = ?, foto = ? WHERE id = ?")->execute([$telefon, $sifre_hash, $foto_adi, $benim_id]);
            $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Profiliniz ve şifreniz güncellendi!</div>";
        } else {
            $db->prepare("UPDATE personeller SET telefon = ?, foto = ? WHERE id = ?")->execute([$telefon, $foto_adi, $benim_id]);
            $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Profil bilgileriniz güncellendi!</div>";
        }
        $gozetmen->logTut($benim_id, 'profil_guncelledi', "Kendi profil bilgilerini güncelledi.");
    } catch(PDOException $e) {
        $mesaj = "<div class='alert error'>Bir hata oluştu.</div>";
    }
}

$bilgiler = $db->query("SELECT * FROM personeller WHERE id = $benim_id")->fetch(PDO::FETCH_ASSOC);
$profil_fotosu = !empty($bilgiler['foto']) ? "uploads/".$bilgiler['foto'] : "https://ui-avatars.com/api/?name=".urlencode($bilgiler['ad_soyad'])."&background=4f46e5&color=fff";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color); }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-color); color: var(--text-main); outline: none;}
        
        .btn { background: var(--primary-color); color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 10px; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; } .error { background: rgba(239,68,68,0.1); color: #ef4444; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php echo $mesaj; ?>
        
        <div class="card">
            <div class="profile-header">
                <img src="<?php echo $profil_fotosu; ?>" alt="Profil" class="profile-img">
                <div>
                    <h2><?php echo htmlspecialchars($bilgiler['ad_soyad']); ?></h2>
                    <p style="color: var(--text-muted);"><?php echo htmlspecialchars($bilgiler['departman']); ?> - <?php echo strtoupper($bilgiler['rol']); ?></p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="eski_foto" value="<?php echo htmlspecialchars($bilgiler['foto']); ?>">
                
                <div class="form-group">
                    <label>Profil Fotoğrafı (JPG, PNG)</label>
                    <input type="file" name="foto" accept="image/*">
                </div>

                <div class="form-group">
                    <label>E-posta Adresi (Değiştirilemez)</label>
                    <input type="email" value="<?php echo htmlspecialchars($bilgiler['email']); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Telefon Numarası</label>
                    <input type="text" name="telefon" value="<?php echo htmlspecialchars($bilgiler['telefon']); ?>" placeholder="05XX XXX XX XX">
                </div>

                <div class="form-group">
                    <label>Yeni Şifre (Sadece değiştirmek isterseniz doldurun)</label>
                    <input type="password" name="yeni_sifre" placeholder="••••••••">
                </div>

                <button type="submit" name="profil_guncelle" class="btn"><i class="fa-solid fa-save"></i> Bilgilerimi Kaydet</button>
            </form>
        </div>
    </main>
</body>
</html>