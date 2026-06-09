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
$rol = $_SESSION['rol'];
$yetkili_mi = ($rol == 'yonetici' || $rol == 'ik');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['degerlendirme_ekle']) && $yetkili_mi) {
    $personel_id = $_POST['personel_id'];
    $donem = trim($_POST['donem']);
    $iletisim = (int)$_POST['iletisim_puani'];
    $takim = (int)$_POST['takim_calismasi_puani'];
    $is_kalitesi = (int)$_POST['is_kalitesi_puani'];
    $yorum = trim($_POST['yorum']);
    
     $ortalama = ($iletisim + $takim + $is_kalitesi) / 3;

    $kontrol = $db->query("SELECT id FROM performans_degerlendirmeleri WHERE personel_id = $personel_id AND donem = '$donem'")->fetch();

    if ($kontrol) {
        $mesaj = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Bu personel için '$donem' döneminde zaten değerlendirme yapılmış!</div>";
    } else {
        $sql = "INSERT INTO performans_degerlendirmeleri (personel_id, degerlendiren_id, donem, iletisim_puani, takim_calismasi_puani, is_kalitesi_puani, ortalama_puan, yorum) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($db->prepare($sql)->execute([$personel_id, $benim_id, $donem, $iletisim, $takim, $is_kalitesi, $ortalama, $yorum])) {
            
            $kim = $db->query("SELECT ad_soyad FROM personeller WHERE id = $personel_id")->fetchColumn();
            $gozetmen->logTut($benim_id, 'performans_ekledi', "'$kim' adlı personele $donem dönemi için $ortalama performans notu verdi.");
            
            $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Değerlendirme başarıyla kaydedildi! Ortalama: " . number_format($ortalama, 1) . "</div>";
        }
    }
}

$aktif_personeller = $db->query("SELECT id, ad_soyad, departman FROM personeller WHERE durum='aktif' ORDER BY ad_soyad")->fetchAll(PDO::FETCH_ASSOC);

if ($yetkili_mi) {
    $sql = "SELECT p.*, per.ad_soyad as calisan, per2.ad_soyad as degerlendiren 
            FROM performans_degerlendirmeleri p 
            JOIN personeller per ON p.personel_id = per.id 
            JOIN personeller per2 ON p.degerlendiren_id = per2.id 
            ORDER BY p.id DESC";
    $degerlendirmeler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT p.*, per.ad_soyad as calisan, per2.ad_soyad as degerlendiren 
            FROM performans_degerlendirmeleri p 
            JOIN personeller per ON p.personel_id = per.id 
            JOIN personeller per2 ON p.degerlendiren_id = per2.id 
            WHERE p.personel_id = $benim_id 
            ORDER BY p.id DESC";
    $degerlendirmeler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Performans Değerlendirme | İK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-color); color: var(--text-main); outline: none; font-family: 'Inter', sans-serif;}
        
        .btn { background: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; } .error { background: rgba(239,68,68,0.1); color: #ef4444; }
        
        .puan-badge { padding: 5px 10px; border-radius: 6px; font-weight: bold; color: white; text-align: center; display: inline-block; min-width: 40px;}
        .puan-iyi { background: #10b981; } /* Yeşil 80-100 */
        .puan-orta { background: #f59e0b; } /* Sarı 50-79 */
        .puan-kotu { background: #ef4444; } /* Kırmızı <50 */
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2><i class="fa-solid fa-chart-line" style="color: var(--primary-color);"></i> Performans Değerlendirme</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Personellerin aylık/dönemsel karnelerini ve geri bildirimlerini yönetin.</p>

        <?php echo $mesaj; ?>

        <?php if($yetkili_mi): ?>
        <div class="card">
            <h3>Yeni Değerlendirme Formu</h3>
            <form method="POST" style="margin-top: 15px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Değerlendirilecek Personel</label>
                        <select name="personel_id" required>
                            <option value="">-- Personel Seçin --</option>
                            <?php foreach($aktif_personeller as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['ad_soyad'] . " (" . $p['departman'] . ")"); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Değerlendirme Dönemi</label>
                        <input type="text" name="donem" required value="<?php echo date('Y F'); ?> Dönemi" placeholder="Örn: 2026 Mayıs Dönemi">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>İletişim Becerisi (0-100)</label>
                        <input type="number" name="iletisim_puani" required min="0" max="100" placeholder="100">
                    </div>
                    <div class="form-group">
                        <label>Takım Çalışması (0-100)</label>
                        <input type="number" name="takim_calismasi_puani" required min="0" max="100" placeholder="100">
                    </div>
                    <div class="form-group">
                        <label>İş Kalitesi / Hız (0-100)</label>
                        <input type="number" name="is_kalitesi_puani" required min="0" max="100" placeholder="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Yönetici Yorumu / Geri Bildirim</label>
                        <textarea name="yorum" rows="3" placeholder="Personelin bu dönemki gelişimi hakkında notlarınız..." required></textarea>
                    </div>
                </div>

                <button type="submit" name="degerlendirme_ekle" class="btn"><i class="fa-solid fa-star"></i> Karnesini Kaydet</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3><?php echo $yetkili_mi ? "Tüm Personel Karneleri" : "Geçmiş Karnelerim"; ?></h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <?php if($yetkili_mi) echo "<th>Personel</th>"; ?>
                            <th>Dönem</th>
                            <th>İletişim</th>
                            <th>Takım</th>
                            <th>İş Kalitesi</th>
                            <th>GENEL ORTALAMA</th>
                            <th>Yorum & Geri Bildirim</th>
                            <?php if($yetkili_mi) echo "<th>Değerlendiren</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($degerlendirmeler as $d): 
                            // Ortalama puana göre renk belirliyoruz
                            $ort = $d['ortalama_puan'];
                            $puanRenk = ($ort >= 80) ? 'puan-iyi' : (($ort >= 50) ? 'puan-orta' : 'puan-kotu');
                        ?>
                        <tr>
                            <?php if($yetkili_mi) echo "<td><strong>" . htmlspecialchars($d['calisan']) . "</strong></td>"; ?>
                            <td><?php echo htmlspecialchars($d['donem']); ?></td>
                            <td><?php echo $d['iletisim_puani']; ?></td>
                            <td><?php echo $d['takim_calismasi_puani']; ?></td>
                            <td><?php echo $d['is_kalitesi_puani']; ?></td>
                            <td><span class="puan-badge <?php echo $puanRenk; ?>"><?php echo number_format($ort, 1); ?></span></td>
                            <td style="font-size: 12px; font-style: italic; max-width: 200px;"><?php echo htmlspecialchars($d['yorum']); ?></td>
                            <?php if($yetkili_mi) echo "<td><i class='fa-solid fa-user-tie' style='color:var(--text-muted); font-size:12px;'></i> " . htmlspecialchars($d['degerlendiren']) . "</td>"; ?>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($degerlendirmeler)): ?>
                            <tr><td colspan="<?php echo $yetkili_mi ? '8' : '6'; ?>" style="text-align:center;">Henüz bir değerlendirme bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>