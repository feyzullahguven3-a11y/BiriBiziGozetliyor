<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'satis']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";
$benim_id = $_SESSION['personel_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['teklif_ekle'])) {
    $musteri_id = $_POST['musteri_id'];
    $konu = trim($_POST['konu']);
    $para_birimi = $_POST['para_birimi'];
    $gecerlilik = $_POST['gecerlilik_tarihi'];

    $urun_adlari = $_POST['urun_adi'];
    $urun_fiyatlari = $_POST['urun_fiyat'];
    
    $kalemler = [];
    $toplam_tutar = 0;
    
    for($i = 0; $i < count($urun_adlari); $i++) {
        if(!empty(trim($urun_adlari[$i]))) {
            $fiyat = (float)$urun_fiyatlari[$i];
            $kalemler[] = [
                'aciklama' => trim($urun_adlari[$i]),
                'fiyat' => $fiyat
            ];
            $toplam_tutar += $fiyat;
        }
    }
    
    $detay_json = json_encode($kalemler, JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO teklifler (musteri_id, olusturan_personel_id, konu, tutar, para_birimi, gecerlilik_tarihi, detay) VALUES (?, ?, ?, ?, ?, ?, ?)";
    if ($db->prepare($sql)->execute([$musteri_id, $benim_id, $konu, $toplam_tutar, $para_birimi, $gecerlilik, $detay_json])) {
        $musteri_ad = $db->query("SELECT firma_adi FROM musteriler WHERE id=$musteri_id")->fetchColumn();
        $gozetmen->logTut($benim_id, 'teklif_olusturdu', "$musteri_ad firmasına $toplam_tutar $para_birimi tutarında teklif hazırladı.");
        $mesaj = "<script>Swal.fire({ title: 'Teklif Oluşturuldu!', text: 'Müşteriye özel çoklu teklif başarıyla kaydedildi.', icon: 'success' });</script>";
    } else {
        $mesaj = "<script>Swal.fire({ title: 'Hata!', text: 'Kayıt sırasında bir hata oluştu.', icon: 'error' });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    $teklif_id = $_POST['teklif_id'];
    $yeni_durum = $_POST['yeni_durum'];
    $db->prepare("UPDATE teklifler SET durum = ? WHERE id = ?")->execute([$yeni_durum, $teklif_id]);
    $mesaj = "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Durum güncellendi', showConfirmButton: false, timer: 3000 });</script>";
}

$musteriler = $db->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
$sql = "SELECT t.*, m.firma_adi FROM teklifler t JOIN musteriler m ON t.musteri_id = m.id ORDER BY t.id DESC";
$teklifler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-file-signature" style="color: var(--primary-color);"></i> Teklifler & Sözleşmeler</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Müşterilere sunulan fiyat tekliflerini oluşturun ve PDF olarak indirin.</p>

    <?php echo $mesaj; ?>

    <div class="card">
        <h3><i class="fa-solid fa-plus"></i> Yeni Teklif Hazırla</h3>
        <form method="POST" style="margin-top: 15px;">
            <div class="form-row">
                <div class="form-group" style="flex: 1.5;">
                    <label>Müşteri / Firma</label>
                    <select name="musteri_id" required>
                        <option value="">-- Seçin --</option>
                        <?php foreach($musteriler as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['firma_adi']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 2;">
                    <label>Teklif Konusu / Proje Adı</label>
                    <input type="text" name="konu" required placeholder="Örn: Kurumsal Web Sitesi Yenileme">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Para Birimi</label>
                    <select name="para_birimi">
                        <option value="TL">Türk Lirası (TL)</option>
                        <option value="USD">Dolar ($)</option>
                        <option value="EUR">Euro (€)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Son Geçerlilik Tarihi</label>
                    <input type="date" name="gecerlilik_tarihi" required value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                </div>
            </div>

            <div style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label style="font-weight: 700; color: var(--text-main); margin: 0;">Teklif Kalemleri (Ürün / Hizmetler)</label>
                    <button type="button" class="btn btn-sm" style="background: #10b981;" onclick="yeniSatirEkle()"><i class="fa-solid fa-plus"></i> Yeni Satır Ekle (Max 5)</button>
                </div>
                
                <div id="kalemler-alani">
                    <div class="kalem-satiri" style="display: flex; gap: 15px; margin-bottom: 10px;">
                        <div class="form-group" style="flex: 3; min-width: auto; margin-bottom: 0;">
                            <input type="text" name="urun_adi[]" required placeholder="1. Ürün veya Hizmet Açıklaması...">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: auto; margin-bottom: 0;">
                            <input type="number" step="0.01" name="urun_fiyat[]" required placeholder="Fiyat">
                        </div>
                        <div style="width: 40px;"></div> </div>
                </div>
                <small style="color: var(--text-muted); display: block; margin-top: 10px;"><i class="fa-solid fa-circle-info"></i> Genel Toplam Tutar, girdiğiniz fiyatlara göre sistem tarafından otomatik hesaplanacaktır.</small>
            </div>

            <button type="submit" name="teklif_ekle" class="btn"><i class="fa-solid fa-file-invoice"></i> Teklifi Kaydet ve Oluştur</button>
        </form>
    </div>

    <div class="card">
        <h3>Geçmiş Teklifler</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Müşteri</th>
                        <th>Konu & Otomatik Tutar</th>
                        <th>Durum</th>
                        <th style="width: 250px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($teklifler as $t): 
                        $cls = ''; $txt = '';
                        if($t['durum'] == 'bekliyor') { $cls='bg-bekliyor'; $txt='Bekliyor'; }
                        if($t['durum'] == 'kabul_edildi') { $cls='bg-kabul'; $txt='Kabul Edildi'; }
                        if($t['durum'] == 'reddedildi') { $cls='bg-red'; $txt='Reddedildi'; }
                    ?>
                    <tr>
                        <td data-sort="<?php echo $t['tarih']; ?>"><?php echo date('d.m.Y', strtotime($t['tarih'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($t['firma_adi']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($t['konu']); ?><br>
                            <span style="font-size: 13px; font-weight: 600; color: var(--primary-color);">
                                <?php echo number_format($t['tutar'], 2, ',', '.') . ' ' . $t['para_birimi']; ?>
                            </span>
                        </td>
                        <td><span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span></td>
                        <td style="display: flex; gap: 8px;">
                            <form method="POST" style="margin:0; padding:0;">
                                <input type="hidden" name="teklif_id" value="<?php echo $t['id']; ?>">
                                <input type="hidden" name="durum_guncelle" value="1">
                                <select name="yeni_durum" onchange="this.form.submit()" style="padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border-color); outline:none; cursor:pointer;">
                                    <option value="bekliyor" <?php echo ($t['durum']=='bekliyor')?'selected':''; ?>>Bekliyor</option>
                                    <option value="kabul_edildi" <?php echo ($t['durum']=='kabul_edildi')?'selected':''; ?>>Kabul Edildi</option>
                                    <option value="reddedildi" <?php echo ($t['durum']=='reddedildi')?'selected':''; ?>>Reddedildi</option>
                                </select>
                            </form>
                            <a href="teklif_belgesi.php?id=<?php echo $t['id']; ?>" target="_blank" class="action-btn" style="background: #374151;">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let satirSayisi = 1;
        function yeniSatirEkle() {
            if(satirSayisi >= 5) {
                Swal.fire({toast: true, position: 'top-end', icon: 'warning', title: 'Maksimum 5 satır ekleyebilirsiniz.', showConfirmButton: false, timer: 3000});
                return;
            }
            const alan = document.getElementById('kalemler-alani');
            const yeniSatir = document.createElement('div');
            yeniSatir.className = 'kalem-satiri';
            yeniSatir.style.cssText = 'display: flex; gap: 15px; margin-bottom: 10px;';
            yeniSatir.innerHTML = `
                <div class="form-group" style="flex: 3; min-width: auto; margin-bottom: 0;">
                    <input type="text" name="urun_adi[]" required placeholder="${satirSayisi + 1}. Ürün veya Hizmet Açıklaması...">
                </div>
                <div class="form-group" style="flex: 1; min-width: auto; margin-bottom: 0;">
                    <input type="number" step="0.01" name="urun_fiyat[]" required placeholder="Fiyat">
                </div>
                <div style="width: 40px; display:flex; align-items:center;">
                    <button type="button" class="action-btn" style="background: var(--danger-color); padding: 12px; border-radius: 8px;" onclick="this.parentElement.parentElement.remove(); satirSayisi--;"><i class="fa-solid fa-trash"></i></button>
                </div>
            `;
            alan.appendChild(yeniSatir);
            satirSayisi++;
        }
    </script>

<?php include 'footer.php'; ?>