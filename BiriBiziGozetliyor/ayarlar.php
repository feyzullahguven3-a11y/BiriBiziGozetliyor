<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'it']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ayarlari_kaydet'])) {
    $sirket_adi = trim($_POST['sirket_adi']);
    $domain = str_replace('@', '', trim($_POST['email_domain']));
    
    $gunler = isset($_POST['gunler']) ? implode(',', $_POST['gunler']) : '1,2,3,4,5';
    $mesai_baslangic = $_POST['mesai_baslangic'];
    $mesai_bitis = $_POST['mesai_bitis'];

    try {
        $sql = "UPDATE ayarlar SET sirket_adi = ?, email_domain = ?, calisma_gunleri = ?, mesai_baslangic = ?, mesai_bitis = ? WHERE id = 1";
        $db->prepare($sql)->execute([$sirket_adi, $domain, $gunler, $mesai_baslangic, $mesai_bitis]);
        
        $gozetmen->logTut($_SESSION['personel_id'], 'ayarlar_guncellendi', "Şirket çalışma takvimini ve mesai ayarlarını güncelledi.");
        $mesaj = "<script>Swal.fire({ title: 'Başarılı!', text: 'Şirket çalışma takvimi ve ayarları güncellendi.', icon: 'success', confirmButtonColor: '#4f46e5' });</script>";
    } catch (PDOException $e) {
        $mesaj = "<script>Swal.fire({ title: 'Hata!', text: 'Ayarlar kaydedilirken bir veritabanı hatası oluştu.', icon: 'error' });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['talep_onayla'])) {
    $talep_id = $_POST['talep_id'];
    $personel_email = $_POST['personel_email'];
    $token = bin2hex(random_bytes(16)); 
    
    $sql = "UPDATE sifre_talepleri SET durum = 'onaylandi', token = ? WHERE id = ?";
    if ($db->prepare($sql)->execute([$token, $talep_id])) {
        $sifirlama_linki = "http://localhost/BiriBiziGozetliyor/sifre_yenile.php?token=" . $token;
        $gozetmen->logTut($_SESSION['personel_id'], 'sifre_onayi', "$personel_email için şifre sıfırlama talebini onayladı.");
        
        $mesaj = "<script>
            Swal.fire({
                title: 'Talep Onaylandı!',
                html: 'Şifre sıfırlama linki üretildi.<br><br><strong>Test Linki:</strong><br><a href=\"$sifirlama_linki\" target=\"_blank\" style=\"color:#4f46e5;\">$sifirlama_linki</a>',
                icon: 'success',
                confirmButtonColor: '#4f46e5'
            });
        </script>";
    }
}

$ayar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$talepler = $db->query("
    SELECT s.id as talep_id, p.ad_soyad, p.email, s.talep_tarihi 
    FROM sifre_talepleri s 
    JOIN personeller p ON s.personel_id = p.id 
    WHERE s.durum = 'bekliyor' 
    ORDER BY s.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$secili_gunler = explode(',', $ayar['calisma_gunleri'] ?? '1,2,3,4,5');

include 'header.php';
?>

    <h2><i class="fa-solid fa-gear" style="color: var(--primary-color);"></i> Sistem Ayarları & Güvenlik</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Şirket çalışma takvimini, mesai saatlerini ve güvenlik taleplerini buradan yönetin.</p>

    <?php echo $mesaj; ?>

    <div class="charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 25px;">
        
        <div class="card" style="margin-bottom: 0;">
            <h3><i class="fa-solid fa-building-shield" style="color: var(--primary-color);"></i> Kurumsal Şirket Ayarları</h3>
            <form method="POST" action="ayarlar.php" style="margin-top: 15px;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Firma / Şirket Resmi Adı</label>
                    <input type="text" name="sirket_adi" value="<?php echo htmlspecialchars($ayar['sirket_adi'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Kurumsal E-Posta Domaini</label>
                    <div style="display: flex; align-items: center;">
                        <span style="background: var(--border-color); padding: 12px 15px; border-radius: 8px 0 0 8px; font-weight: bold; color: var(--text-muted);">@</span>
                        <input type="text" name="email_domain" value="<?php echo htmlspecialchars($ayar['email_domain'] ?? ''); ?>" required style="border-radius: 0 8px 8px 0; border-left: none;">
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Mesai Başlangıç</label>
                        <input type="time" name="mesai_baslangic" value="<?php echo date('H:i', strtotime($ayar['mesai_baslangic'] ?? '09:00')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Mesai Bitiş</label>
                        <input type="time" name="mesai_bitis" value="<?php echo date('H:i', strtotime($ayar['mesai_bitis'] ?? '18:00')); ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Haftalık Çalışma Günleri</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px; font-size: 13px;">
                        <?php 
                        $hafta = [1=>'Pzt', 2=>'Sal', 3=>'Çar', 4=>'Per', 5=>'Cum', 6=>'Cmt', 7=>'Pzr'];
                        foreach($hafta as $no => $isim):
                            $checked = in_array($no, $secili_gunler) ? 'checked' : '';
                        ?>
                            <label style="display: flex; align-items: center; gap: 5px; background: var(--bg-color); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color); cursor: pointer;">
                                <input type="checkbox" name="gunler[]" value="<?php echo $no; ?>" <?php echo $checked; ?>> <?php echo $isim; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" name="ayarlari_kaydet" class="btn" style="width: 100%;"><i class="fa-solid fa-floppy-disk"></i> Takvimi ve Ayarları Kaydet</button>
            </form>
        </div>

        <div class="card" style="margin-bottom: 0; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(79, 70, 229, 0.02) 100%);">
            <h3><i class="fa-solid fa-circle-info" style="color: var(--primary-color);"></i> Akıllı İK Entegrasyonu</h3>
            <div style="margin-top: 15px; font-size: 14px; line-height: 1.8;">
                <p>📅 <strong>Otomatik İşbaşı Hesaplama:</strong> Sol tarafta seçtiğiniz çalışma günleri, personellerin izin formlarındaki <u>İşe Başlama Tarihi</u> hesaplanırken baz alınır.</p>
                <p style="margin-top: 10px;">🏝️ Örneğin; Hafta sonu (Cumartesi-Pazar) seçimini kaldırırsanız ve bir personel Cuma günü izinli ise (veya izninin son günü Cuma ise), sistem işbaşı tarihini otomatik olarak bir sonraki ilk çalışma günü olan <strong>Pazartesi</strong> gününe kaydırır.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <h3><i class="fa-solid fa-key" style="color: var(--warning-color);"></i> Bekleyen Şifre Sıfırlama Talepleri</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table id="logTable" class="display" style="width:100%;">
                <thead>
                    <tr>
                        <th>Personel Adı</th>
                        <th>E-Posta Adresi</th>
                        <th>Talep Tarihi</th>
                        <th style="width: 200px;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($talepler as $t): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($t['ad_soyad']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($t['email']); ?></code></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($t['talep_tarihi'])); ?></td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="talep_id" value="<?php echo $t['talep_id']; ?>">
                                <input type="hidden" name="personel_email" value="<?php echo htmlspecialchars($t['email']); ?>">
                                <button type="submit" name="talep_onayla" class="action-btn" style="background: var(--success-color); width: 100%; justify-content: center;"><i class="fa-solid fa-check-double"></i> Onayla & Link Üret</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'footer.php'; ?>