<?php
// destek.php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'satis']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";
$benim_id = $_SESSION['personel_id'];


$musteriler = $db->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
$departmanlar = $db->query("SELECT ad FROM departmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['talep_ekle'])) {
    $musteri_id = $_POST['musteri_id'];
    $konu = trim($_POST['konu']);
    $oncelik = $_POST['oncelik'];
    $ilgili_departman = trim($_POST['ilgili_departman']);
    $detay = trim($_POST['mesaj']);

    $sql = "INSERT INTO destek_talepleri (musteri_id, olusturan_personel_id, konu, oncelik, mesaj, ilgili_departman) VALUES (?, ?, ?, ?, ?, ?)";
    
    if ($db->prepare($sql)->execute([$musteri_id, $benim_id, $konu, $oncelik, $detay, $ilgili_departman])) {
        
        $musteri_ad = $db->query("SELECT firma_adi FROM musteriler WHERE id=$musteri_id")->fetchColumn();
        $gozetmen->logTut($benim_id, 'destek_talebi', "$musteri_ad için '$konu' konulu destek talebi ($ilgili_departman) departmanına açıldı.");
        
        if (!empty($ilgili_departman)) {
            $departman_uyeleri = $db->prepare("SELECT id FROM personeller WHERE departman = ? AND durum = 'aktif'");
            $departman_uyeleri->execute([$ilgili_departman]);
            $uyeler = $departman_uyeleri->fetchAll(PDO::FETCH_ASSOC);

            if (count($uyeler) > 0) {
                $gun_ekle = ($oncelik == 'yüksek') ? '+1 days' : (($oncelik == 'orta') ? '+3 days' : '+7 days');
                $bitis_tarihi = date('Y-m-d', strtotime($gun_ekle));
                
                $g_baslik = "Müşteri Destek Talebi: " . $musteri_ad;
                $g_aciklama = "Konu: $konu\nÖncelik: $oncelik\n\nDetay:\n$detay";
                
             $gorev_ekle_sql = "INSERT INTO gorevler (baslik, aciklama, atayan_id, atanan_id, bitis_tarihi, durum) VALUES (?, ?, ?, ?, ?, 'bekliyor')";
                $stmt_gorev = $db->prepare($gorev_ekle_sql);

                foreach ($uyeler as $uye) {
                   
                    $stmt_gorev->execute([$g_baslik, $g_aciklama, $benim_id, $uye['id'], $bitis_tarihi]);
                }
            }
        }
        
        $mesaj = "<script>Swal.fire({ title: 'Talep ve Görevler Açıldı!', text: 'Destek talebi oluşturuldu ve $ilgili_departman departmanındaki personele görev olarak atandı.', icon: 'success', confirmButtonColor: '#4f46e5' });</script>";
    } else {
        $mesaj = "<script>Swal.fire({ title: 'Hata!', text: 'Talep oluşturulurken bir sorun meydana geldi.', icon: 'error' });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    $talep_id = $_POST['talep_id'];
    $yeni_durum = $_POST['yeni_durum'];

    $db->prepare("UPDATE destek_talepleri SET durum = ? WHERE id = ?")->execute([$yeni_durum, $talep_id]);
    $gozetmen->logTut($benim_id, 'destek_guncelleme', "$talep_id ID'li talebin durumunu '$yeni_durum' yaptı.");
    
    $mesaj = "<script>
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: 'Talep durumu güncellendi', showConfirmButton: false, timer: 3000
        });
    </script>";
}

$sql = "SELECT d.*, m.firma_adi, p.ad_soyad as personel_adi 
        FROM destek_talepleri d 
        JOIN musteriler m ON d.musteri_id = m.id 
        JOIN personeller p ON d.olusturan_personel_id = p.id 
        ORDER BY d.durum ASC, d.id DESC"; 
$talepler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-headset" style="color: var(--primary-color);"></i> Destek / Arıza Talepleri (Ticketing)</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Müşterilerden gelen sorunları takip edin ve çözüm süreçlerini yönetin.</p>

    <?php echo $mesaj; ?>

    <div class="card">
        <h3><i class="fa-solid fa-ticket"></i> Yeni Talep Oluştur</h3>
        <form method="POST" style="margin-top: 15px;">
            <div class="form-row">
                <div class="form-group">
                    <label>İlgili Müşteri / Firma</label>
                    <select name="musteri_id" required>
                        <option value="">-- Müşteri Seçin --</option>
                        <?php foreach($musteriler as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['firma_adi']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Konu (Kısaca)</label>
                    <input type="text" name="konu" required placeholder="Örn: Sunucu erişim sorunu">
                </div>
                <div class="form-group" style="flex: 0.8;">
                    <label>Öncelik Derecesi</label>
                    <select name="oncelik">
                        <option value="düşük">Düşük Öncelik</option>
                        <option value="orta" selected>Orta Öncelik</option>
                        <option value="yüksek">Yüksek (Acil)</option>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 0.8;">
                    <label>Görevlendirilecek Departman</label>
                    <select name="ilgili_departman" required>
                        <option value="">-- Departman Seçin --</option>
                        <?php foreach($departmanlar as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['ad']); ?>"><?php echo htmlspecialchars($d['ad']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Talep Detayı / Müşteri Mesajı</label>
                    <textarea name="mesaj" rows="3" placeholder="Müşterinin ilettiği sorunun tüm detayları..." required></textarea>
                </div>
            </div>

            <button type="submit" name="talep_ekle" class="btn"><i class="fa-solid fa-plus"></i> Talebi Aç ve Görev Ata</button>
        </form>
    </div>

    <div class="card">
        <h3>Açık ve Geçmiş Talepler</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Müşteri</th>
                        <th>Konu & Detay</th>
                        <th>İlgili Departman</th>
                        <th>Öncelik</th>
                        <th>Durum</th>
                        <th style="width: 140px;">Hızlı İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($talepler as $t): 
                        $durumClass = '';
                        switch($t['durum']) {
                            case 'açık': $durumClass = 'bg-red'; break;
                            case 'işlemde': $durumClass = 'bg-islemde'; break;
                            case 'çözüldü': $durumClass = 'bg-cozuldu'; break;
                        }

                        $oncClass = ''; $oncIcon = '';
                        switch($t['oncelik']) {
                            case 'düşük': $oncClass = 'color: var(--success-color);'; $oncIcon = 'fa-arrow-down'; break;
                            case 'orta': $oncClass = 'color: var(--warning-color);'; $oncIcon = 'fa-minus'; break;
                            case 'yüksek': $oncClass = 'color: var(--danger-color); font-weight: bold;'; $oncIcon = 'fa-arrow-up'; break;
                        }
                    ?>
                    <tr style="<?php echo ($t['durum'] == 'çözüldü') ? 'opacity: 0.6;' : ''; ?>">
                        <td data-sort="<?php echo $t['tarih']; ?>"><?php echo date('d.m.Y H:i', strtotime($t['tarih'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($t['firma_adi']); ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($t['konu']); ?></strong><br>
                            <span style="font-size: 11px; color: var(--text-muted);" title="<?php echo htmlspecialchars($t['mesaj']); ?>">
                                <?php echo mb_strlen($t['mesaj']) > 50 ? mb_substr(htmlspecialchars($t['mesaj']), 0, 50) . '...' : htmlspecialchars($t['mesaj']); ?>
                            </span>
                        </td>
                        <td><span style="background: rgba(79,70,229,0.1); color: var(--primary-color); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;"><?php echo htmlspecialchars($t['ilgili_departman'] ?? 'Atanmadı'); ?></span></td>
                        
                        <td style="<?php echo $oncClass; ?>"><i class="fa-solid <?php echo $oncIcon; ?>"></i> <?php echo strtoupper($t['oncelik']); ?></td>
                        <td><span class="badge <?php echo $durumClass; ?>"><?php echo strtoupper($t['durum']); ?></span></td>
                        <td>
                            <form method="POST" style="margin:0; padding:0; display:flex; gap: 5px;">
                                <input type="hidden" name="talep_id" value="<?php echo $t['id']; ?>">
                                <input type="hidden" name="durum_guncelle" value="1">
                                <select name="yeni_durum" onchange="this.form.submit()" style="padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-main); outline:none; cursor:pointer;">
                                    <option value="açık" <?php echo ($t['durum']=='açık')?'selected':''; ?>>Açık</option>
                                    <option value="işlemde" <?php echo ($t['durum']=='işlemde')?'selected':''; ?>>İşlemde</option>
                                    <option value="çözüldü" <?php echo ($t['durum']=='çözüldü')?'selected':''; ?>>Çözüldü</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php 
include 'footer.php'; 
?>