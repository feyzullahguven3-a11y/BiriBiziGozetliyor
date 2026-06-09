<?php
// izinler.php
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

$kalan_izin = $db->query("SELECT kalan_yillik_izin FROM personeller WHERE id = $benim_id")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['izin_talep'])) {
    $baslangic = $_POST['baslangic_tarihi'];
    $bitis = $_POST['bitis_tarihi'];
    $tur = $_POST['izin_turu'];
    $aciklama = trim($_POST['aciklama']);

    $b1 = new DateTime($baslangic);
    $b2 = new DateTime($bitis);
    $istenen_gun = $b1->diff($b2)->days + 1;

    if ($istenen_gun > $kalan_izin) {
        $mesaj = "<script>
            Swal.fire({
                title: 'Yetersiz İzin Bakiyesi!',
                html: 'Talep ettiğiniz <strong>$istenen_gun Gün</strong>, kalan bakiye olan <strong>$kalan_izin Gün</strong> sınırını aşıyor.<br><br>Lütfen İnsan Kaynakları ile görüşün.',
                icon: 'error',
                confirmButtonText: 'Anladım'
            });
        </script>";
    } else {
        $sql = "INSERT INTO izinler (personel_id, baslangic_tarihi, bitis_tarihi, izin_turu, aciklama) VALUES (?, ?, ?, ?, ?)";
        if ($db->prepare($sql)->execute([$benim_id, $baslangic, $bitis, $tur, $aciklama])) {
            $gozetmen->logTut($benim_id, 'izin_talebi', "$istenen_gun günlük '$tur' talebinde bulundu.");
            
            $ik_personelleri = $db->query("SELECT id FROM personeller WHERE rol = 'ik'")->fetchAll();
            foreach($ik_personelleri as $ik) {
                if($ik['id'] != $benim_id) {
                    $db->prepare("INSERT INTO bildirimler (personel_id, baslik, icerik, link) VALUES (?, ?, ?, ?)")->execute([$ik['id'], 'Yeni İzin Talebi', "Sistemde onayınızı bekleyen yeni bir izin talebi var.", 'izinler.php']);
                }
            }
            $mesaj = "<script>Swal.fire({ title: 'Talep İletildi!', text: 'İzin talebiniz ($istenen_gun Gün) başarıyla İnsan Kaynaklarına gönderildi.', icon: 'success' });</script>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    if ($rol == 'ik') {
        $izin_id = $_POST['izin_id'];
        $yeni_durum = $_POST['yeni_durum'];
        $talep_eden = $_POST['talep_eden_id'];

        if ($yeni_durum == 'onaylandi') {
            $izin_bilgi = $db->query("SELECT baslangic_tarihi, bitis_tarihi FROM izinler WHERE id = $izin_id")->fetch();
            $d1 = new DateTime($izin_bilgi['baslangic_tarihi']);
            $d2 = new DateTime($izin_bilgi['bitis_tarihi']);
            $dusulecek_gun = $d1->diff($d2)->days + 1;

            $db->prepare("UPDATE personeller SET kalan_yillik_izin = kalan_yillik_izin - ? WHERE id = ?")->execute([$dusulecek_gun, $talep_eden]);
            
            if($talep_eden == $benim_id) { $kalan_izin -= $dusulecek_gun; }
            

            $db->prepare("UPDATE izinler SET durum = ?, onaylayan_id = ? WHERE id = ?")->execute([$yeni_durum, $benim_id, $izin_id]);
        } else {
            $db->prepare("UPDATE izinler SET durum = ?, onaylayan_id = NULL WHERE id = ?")->execute([$yeni_durum, $izin_id]);
        }

        $gozetmen->logTut($benim_id, 'izin_onayi', "İzin ID: $izin_id durumunu '$yeni_durum' yaptı.");
        
        $durum_tr = ($yeni_durum == 'onaylandi') ? 'ONAYLANDI' : 'REDDEDİLDİ';
        $db->prepare("INSERT INTO bildirimler (personel_id, baslik, icerik, link) VALUES (?, ?, ?, ?)")->execute([$talep_eden, 'İzin Talebi Sonucu', "İzin talebiniz İK tarafından $durum_tr.", 'izinler.php']);

        $mesaj = "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'İzin durumu güncellendi.', showConfirmButton: false, timer: 3000 });</script>";
    }
}

if ($rol == 'yonetici' || $rol == 'ik') {
    $sql = "SELECT i.*, p.ad_soyad FROM izinler i JOIN personeller p ON i.personel_id = p.id ORDER BY i.durum ASC, i.id DESC";
} else {
    $sql = "SELECT i.*, p.ad_soyad FROM izinler i JOIN personeller p ON i.personel_id = p.id WHERE i.personel_id = $benim_id ORDER BY i.id DESC";
}
$izinler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-calendar-minus" style="color: var(--primary-color);"></i> İzin Yönetimi</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Personel izin taleplerini ve şirket içi devamsızlıkları takip edin.</p>

    <div style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fa-solid fa-umbrella-beach" style="font-size: 24px; color: #4f46e5;"></i>
            <div>
                <h4 style="margin: 0; color: #374151;">Yıllık İzin Bakiyesi</h4>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">Kullanılabilir durumdaki toplam ücretli izin hakkınız.</p>
            </div>
        </div>
        <div style="font-size: 24px; font-weight: 800; color: #4f46e5;">
            <?php echo $kalan_izin; ?> Gün
        </div>
    </div>

    <?php echo $mesaj; ?>

    <div class="card">
        <h3><i class="fa-solid fa-paper-plane"></i> Yeni İzin Talebi Oluştur</h3>
        <form method="POST" style="margin-top: 15px;">
            <div class="form-row">
                <div class="form-group">
                    <label>İzin Türü</label>
                    <select name="izin_turu" required>
                        <option value="Yıllık İzin">Yıllık İzin</option>
                        <option value="Mazeret İzni">Mazeret İzni</option>
                        <option value="Hastalık / Sağlık Raporu">Hastalık / Sağlık Raporu</option>
                        <option value="Ücretsiz İzin">Ücretsiz İzin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarihi" required>
                </div>
                <div class="form-group">
                    <label>Bitiş Tarihi</label>
                    <input type="date" name="bitis_tarihi" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Açıklama / Mazeret Detayı</label>
                    <textarea name="aciklama" rows="2" placeholder="İzin talebinizin nedeni..." required></textarea>
                </div>
            </div>
            <button type="submit" name="izin_talep" class="btn"><i class="fa-solid fa-check"></i> Talebi İnsan Kaynaklarına Gönder</button>
        </form>
    </div>

    <div class="card">
        <h3><?php echo ($rol == 'yonetici' || $rol == 'ik') ? "Tüm Personel İzin Talepleri" : "İzin Geçmişim"; ?></h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih Aralığı</th>
                        <?php if($rol == 'yonetici' || $rol == 'ik') echo "<th>Personel</th>"; ?>
                        <th>İzin Türü & Açıklama</th>
                        <th>Durum</th>
                        <th style="width: 200px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($izinler as $i): 
                        $cls = ''; $txt = '';
                        if($i['durum'] == 'bekliyor') { $cls='bg-bekliyor'; $txt='Onay Bekliyor'; }
                        if($i['durum'] == 'onaylandi') { $cls='bg-kabul'; $txt='Onaylandı'; }
                        if($i['durum'] == 'reddedildi') { $cls='bg-red'; $txt='Reddedildi'; }
                    ?>
                    <tr>
                        <td data-sort="<?php echo $i['baslangic_tarihi']; ?>">
                            <strong><?php echo date('d.m.Y', strtotime($i['baslangic_tarihi'])); ?></strong> <br>
                            <span style="font-size: 11px; color: var(--text-muted);">Bitiş: <?php echo date('d.m.Y', strtotime($i['bitis_tarihi'])); ?></span>
                        </td>
                        
                        <?php if($rol == 'yonetici' || $rol == 'ik'): ?>
                            <td><i class="fa-solid fa-user" style="font-size:11px; color:var(--text-muted);"></i> <strong><?php echo htmlspecialchars($i['ad_soyad']); ?></strong></td>
                        <?php endif; ?>
                        
                        <td>
                            <strong><?php echo htmlspecialchars($i['izin_turu']); ?></strong><br>
                            <span style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($i['aciklama']); ?></span>
                        </td>
                        
                        <td><span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span></td>
                        
                        <td style="display: flex; align-items: center; gap: 8px;">
                            <?php if($rol == 'ik' && $i['durum'] == 'bekliyor'): ?>
                                <form method="POST" style="margin:0; padding:0; display:flex; gap: 5px;">
                                    <input type="hidden" name="izin_id" value="<?php echo $i['id']; ?>">
                                    <input type="hidden" name="talep_eden_id" value="<?php echo $i['personel_id']; ?>">
                                    <input type="hidden" name="durum_guncelle" value="1">
                                    <button type="submit" name="yeni_durum" value="onaylandi" class="action-btn btn-success" title="Onayla"><i class="fa-solid fa-check"></i></button>
                                    <button type="submit" name="yeni_durum" value="reddedildi" class="action-btn btn-danger" title="Reddet"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                            <?php endif; ?>

                            <a href="izin_belgesi.php?id=<?php echo $i['id']; ?>" target="_blank" class="action-btn" style="background: #374151;">
                                <i class="fa-solid fa-file-pdf"></i> PDF Form
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'footer.php'; ?>