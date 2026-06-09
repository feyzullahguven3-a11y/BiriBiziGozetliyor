<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'satis']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firsat_ekle'])) {
    $musteri_id = $_POST['musteri_id'];
    $firsat_adi = trim($_POST['firsat_adi']);
    $tutar = $_POST['tahmini_tutar'];
    $asama = $_POST['asama'];
    $personel_id = $_SESSION['personel_id'];

    $sql = "INSERT INTO satis_firsatlari (musteri_id, ilgilenen_personel_id, firsat_adi, tahmini_tutar, asama) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$musteri_id, $personel_id, $firsat_adi, $tutar, $asama])) {
        // Log tutuyoruz
        $gozetmen->logTut($personel_id, 'firsat_ekledi', "'$firsat_adi' adında yeni bir satış fırsatı oluşturdu.");
        $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Satış fırsatı başarıyla eklendi!</div>";
    } else {
        $mesaj = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Bir hata oluştu.</div>";
    }
}

$musteriler_stmt = $db->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi ASC");
$musteriler_liste = $musteriler_stmt->fetchAll(PDO::FETCH_ASSOC);


$firsatlar_sql = "SELECT s.*, m.firma_adi, p.ad_soyad as personel_adi 
                  FROM satis_firsatlari s 
                  JOIN musteriler m ON s.musteri_id = m.id 
                  JOIN personeller p ON s.ilgilenen_personel_id = p.id 
                  ORDER BY s.id DESC";
$firsatlar = $db->query($firsatlar_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Satış Fırsatları | CRM Modülü</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-color); color: var(--text-main); outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-color); }
        
        .btn { background: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); text-transform: uppercase; font-size: 12px; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }
        .error { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }

        /* Aşama Renkleri */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .bg-gorusme { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .bg-teklif { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .bg-kazanildi { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .bg-kaybedildi { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2><i class="fa-solid fa-handshake" style="color: var(--primary-color);"></i> Satış Fırsatları</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Müşterilerle olan potansiyel iş fırsatlarını ve satış aşamalarını takip edin.</p>

        <?php echo $mesaj; ?>

        <div class="card">
            <h3>Yeni Fırsat Oluştur</h3>
            <form method="POST" action="satis_firsatlari.php" style="margin-top: 15px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>İlgili Müşteri/Firma</label>
                        <select name="musteri_id" required>
                            <option value="">-- Müşteri Seçin --</option>
                            <?php foreach($musteriler_liste as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['firma_adi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fırsat Adı (Proje/Ürün)</label>
                        <input type="text" name="firsat_adi" required placeholder="Örn: 50 Adet Lisans Satışı">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahmini Tutar (₺)</label>
                        <input type="number" step="0.01" name="tahmini_tutar" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Satış Aşaması</label>
                        <select name="asama">
                            <option value="gorusme">🗣️ İlk Görüşme</option>
                            <option value="teklif_verildi">📄 Teklif Verildi</option>
                            <option value="kazanildi">✅ Kazanıldı (Satış)</option>
                            <option value="kaybedildi">❌ Kaybedildi</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="firsat_ekle" class="btn"><i class="fa-solid fa-plus"></i> Fırsatı Kaydet</button>
            </form>
        </div>

        <div class="card">
            <h3>Tüm Satış Fırsatları</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Fırsat / Proje</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Aşama</th>
                            <th>İlgilenen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($firsatlar as $firsat): 
                            // Aşamaya göre badge rengi belirleme
                            $badgeClass = '';
                            $asamaMetni = '';
                            switch($firsat['asama']) {
                                case 'gorusme': $badgeClass = 'bg-gorusme'; $asamaMetni = 'Görüşme'; break;
                                case 'teklif_verildi': $badgeClass = 'bg-teklif'; $asamaMetni = 'Teklif Verildi'; break;
                                case 'kazanildi': $badgeClass = 'bg-kazanildi'; $asamaMetni = 'Kazanıldı'; break;
                                case 'kaybedildi': $badgeClass = 'bg-kaybedildi'; $asamaMetni = 'Kaybedildi'; break;
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($firsat['firsat_adi']); ?></strong></td>
                            <td><i class="fa-solid fa-building" style="color: var(--text-muted); font-size: 12px;"></i> <?php echo htmlspecialchars($firsat['firma_adi']); ?></td>
                            <td><strong><?php echo number_format($firsat['tahmini_tutar'], 2, ',', '.'); ?> ₺</strong></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $asamaMetni; ?></span></td>
                            <td><?php echo htmlspecialchars($firsat['personel_adi']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($firsatlar)): ?>
                            <tr><td colspan="5" style="text-align:center;">Henüz satış fırsatı eklenmemiş. Önce bir müşteri seçip fırsat oluşturun!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>