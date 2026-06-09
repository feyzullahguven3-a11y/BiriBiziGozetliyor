<?php

session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->oturumKontrol();

if (!isset($_GET['id'])) { die("Belge bulunamadı."); }
$izin_id = (int)$_GET['id'];
$benim_id = $_SESSION['personel_id'];
$rol = $_SESSION['rol'];

$sistem = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$sirket_ismi = htmlspecialchars($sistem['sirket_adi'] ?? 'Şirket Adı Tanımlanmamış');
$calisma_gunleri_arr = explode(',', $sistem['calisma_gunleri'] ?? '1,2,3,4,5');

$sql = "SELECT i.*, p.ad_soyad, p.tc_kimlik, p.telefon, p.departman, p.rol as personel_rol, p.kayit_tarihi,
               o.ad_soyad as onaylayan_ik_adi
        FROM izinler i 
        JOIN personeller p ON i.personel_id = p.id 
        LEFT JOIN personeller o ON i.onaylayan_id = o.id
        WHERE i.id = $izin_id";
$belge = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$belge) { die("Kayıt bulunamadı."); }

if ($rol != 'yonetici' && $rol != 'ik' && $belge['personel_id'] != $benim_id) {
    die("Bu belgeyi görüntüleme yetkiniz bulunmamaktadır.");
}

$baslangic = new DateTime($belge['baslangic_tarihi']);
$bitis = new DateTime($belge['bitis_tarihi']);
$sure_gun = $baslangic->diff($bitis)->days + 1;

$ise_baslama = new DateTime($belge['bitis_tarihi']);
do {
    $ise_baslama->modify('+1 day'); 
    $haftanin_gunu = $ise_baslama->format('N'); 
} while (!in_array($haftanin_gunu, $calisma_gunleri_arr));

$ise_baslama_tarihi = $ise_baslama->format('d.m.Y');
$izin_avansi = 0; 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İzin Talep Formu - <?php echo htmlspecialchars($belge['ad_soyad']); ?></title>
    <style>

        @page { size: A4; margin: 0; } 
        
        body { font-family: "Times New Roman", Times, serif; color: #000; background: #e5e7eb; line-height: 1.5; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .page { width: 210mm; min-height: 297mm; padding: 15mm 20mm; background: white; box-sizing: border-box; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        h2 { text-align: center; text-decoration: underline; margin-bottom: 25px; font-size: 18px; text-transform: uppercase;}
        
        .section-title { font-weight: bold; margin-top: 15px; font-size: 15px; border-bottom: 1px solid #000; padding-bottom: 3px;}
        .form-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .form-table td { padding: 6px 8px; border: 1px solid #000; font-size: 13px; }
        .label { font-weight: bold; width: 35%; background-color: #f9f9f9; }
        
        .imza-alani { width: 100%; margin-top: 35px; border-collapse: collapse; text-align: center; }
        .imza-alani td { width: 50%; padding-bottom: 45px; font-weight: bold; font-size: 13px; }
        
        @media print {
            body { background: white; padding: 0; display: block; }
            .page { width: 210mm; height: 297mm; box-shadow: none; margin: 0; padding: 15mm 20mm; overflow: hidden; }
        }
    </style>
</head>
<body onload="window.print()"> 
    <div class="page">
        <h2>YILLIK İZİN TALEP FORMU</h2>

        <div class="section-title">İŞVERENİN</div>
        <table class="form-table">
            <tr><td class="label">Adı Ünvanı:</td><td><strong><?php echo mb_strtoupper($sirket_ismi); ?></strong></td></tr>
            <tr><td class="label">Adresi:</td><td>Merkez / Türkiye</td></tr>
            <tr><td class="label">SGK İşyeri Sicil Nosu:</td><td>......................................</td></tr>
        </table>

        <div class="section-title">PERSONELİN</div>
        <table class="form-table">
            <tr><td class="label">Personel Kodu:</td><td>PER-<?php echo str_pad($belge['personel_id'], 4, "0", STR_PAD_LEFT); ?></td></tr>
            <tr><td class="label">Adı Soyadı:</td><td><?php echo htmlspecialchars($belge['ad_soyad']); ?></td></tr>
            <tr><td class="label">SGK Sicil No (T.C. Kimlik No):</td><td><?php echo !empty($belge['tc_kimlik']) ? htmlspecialchars($belge['tc_kimlik']) : '.......................'; ?></td></tr>
            <tr><td class="label">İşe Giriş Tarihi:</td><td><?php echo date('d.m.Y', strtotime($belge['kayit_tarihi'])); ?></td></tr>
            <tr><td class="label">Görevi:</td><td><?php echo strtoupper(htmlspecialchars($belge['personel_rol'])); ?></td></tr>
            <tr><td class="label">Departmanı:</td><td><?php echo htmlspecialchars($belge['departman']); ?></td></tr>
            <tr><td class="label">Adres ve İletişim Bilgileri:</td><td><?php echo htmlspecialchars($belge['telefon'] ?? '.......................'); ?></td></tr>
        </table>

        <div class="section-title">İZNİN</div>
        <table class="form-table">
            <tr><td class="label">İzin Türü:</td><td><strong><?php echo htmlspecialchars($belge['izin_turu']); ?></strong></td></tr>
            <tr><td class="label">Süresi (Gün):</td><td><?php echo $sure_gun; ?> Gün</td></tr>
            <tr><td class="label">Başlangıç Tarihi:</td><td><?php echo date('d.m.Y', strtotime($belge['baslangic_tarihi'])); ?></td></tr>
            <tr><td class="label">Bitiş Tarihi:</td><td><?php echo date('d.m.Y', strtotime($belge['bitis_tarihi'])); ?></td></tr>
            <tr><td class="label">İşe Başlama Tarihi:</td><td><?php echo $ise_baslama_tarihi; ?></td></tr>
            <tr><td class="label">İzin Avansı (TL):</td><td><?php echo number_format($izin_avansi, 2, ',', '.'); ?> TL</td></tr>
            <tr><td class="label">İzin Talep Nedeni:</td><td><?php echo htmlspecialchars($belge['aciklama']); ?></td></tr>
        </table>

        <table class="imza-alani">
            <tr>
                <td>Personel<br>İmza</td>
                <td>
                    <?php if($belge['durum'] == 'onaylandi' && !empty($belge['onaylayan_ik_adi'])): ?>
                        Onaylayan (İK)<br><span style="font-size:13px; font-weight:normal; text-decoration:underline;"><?php echo htmlspecialchars($belge['onaylayan_ik_adi']); ?></span><br>İmza
                    <?php else: ?>
                        Düzenleyen (İK)<br>İmza
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="font-weight:normal; font-size:12px;">Talep Tarihi: <?php echo date('d.m.Y', strtotime($belge['talep_tarihi'])); ?></td>
                <td style="font-weight:normal; font-size:12px;">Düzenleme/Onay Tarihi: <?php echo ($belge['durum'] == 'onaylandi') ? date('d.m.Y', strtotime($belge['tarih'] ?? 'now')) : date('d.m.Y'); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>