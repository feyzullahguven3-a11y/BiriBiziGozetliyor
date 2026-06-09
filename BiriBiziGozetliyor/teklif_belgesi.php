<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->oturumKontrol();

if (!isset($_GET['id'])) { die("Geçersiz teklif."); }
$teklif_id = (int)$_GET['id'];

$sistem = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$sirket_ismi = htmlspecialchars($sistem['sirket_adi'] ?? 'Şirket Adı Tanımlanmamış');

$sql = "SELECT t.*, m.firma_adi, m.yetkili_kisi, m.adres, m.telefon 
        FROM teklifler t 
        JOIN musteriler m ON t.musteri_id = m.id 
        WHERE t.id = $teklif_id";
$b = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$b) { die("Teklif bulunamadı."); }

$ara_toplam = $b['tutar'];
$kdv = $ara_toplam * 0.20;
$genel_toplam = $ara_toplam + $kdv;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif Formu - <?php echo htmlspecialchars($b['firma_adi']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        @page { size: A4; margin: 0; } 

        body { font-family: 'Inter', sans-serif; background: #525659; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        
        .toolbar { width: 210mm; display: flex; justify-content: space-between; margin-bottom: 20px; }
        .download-btn { background: #4f46e5; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;}
        .download-btn:hover { background: #4338ca; }
        
        .a4-page { width: 210mm; min-height: 297mm; background: #ffffff; padding: 20mm; box-sizing: border-box; box-shadow: 0 10px 30px rgba(0,0,0,0.5); position: relative; color: #1f2937;}
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1f2937; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-icon { font-size: 40px; color: #4f46e5; }
        .company-name { font-size: 24px; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: -0.5px;}
        .company-sub { font-size: 13px; color: #6b7280; margin-top: 5px; }
        
        .info-section { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .client-info { width: 55%; }
        .client-info h3 { margin: 0 0 10px 0; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
        .client-info p { margin: 4px 0; font-size: 14px; }
        
        .meta-table { width: 40%; border-collapse: collapse; }
        .meta-table td { padding: 6px 10px; border: 1px solid #e5e7eb; font-size: 12px;}
        .meta-table td:first-child { background: #f3f4f6; font-weight: 700; width: 40%; }
        
        .doc-title { text-align: center; font-size: 20px; font-weight: 800; margin-bottom: 25px; letter-spacing: 2px; text-decoration: underline; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td { border: 1px solid #d1d5db; padding: 12px; font-size: 13px; }
        .items-table th { background: #1f2937; color: white; font-weight: 600; text-transform: uppercase; font-size: 11px; text-align: left;}
        
        .totals-section { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .bank-info { width: 50%; font-size: 12px; line-height: 1.6;}
        .bank-info h4 { margin: 0 0 5px 0; font-size: 13px; text-decoration: underline;}
        
        .totals-table { width: 40%; border-collapse: collapse; }
        .totals-table td { padding: 8px 12px; border: 1px solid #d1d5db; font-size: 13px; }
        .totals-table tr td:first-child { font-weight: 600; background: #f9fafb; text-align: right; }
        .totals-table tr:last-child td { font-weight: 800; font-size: 15px; background: #eef2ff; color: #4f46e5;}
        
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; text-align: center; }
        .sign-box { width: 40%; }
        .sign-box p { margin: 5px 0; font-size: 13px; font-weight: 600; }
        .sign-line { border-top: 1px solid #1f2937; margin-top: 60px; padding-top: 10px; font-size: 11px; color: #6b7280;}
        
        @media print { 
            body { background: white; padding: 0; display: block; } 
            .toolbar { display: none; } 
            .a4-page { width: 210mm; height: 297mm; box-shadow: none; margin: 0; padding: 20mm; overflow: hidden; } 
        }
    </style>
</head>
<body>

    <div class="toolbar" data-html2canvas-ignore="true">
        <div style="color: white; font-size: 14px;">Belgeyi kontrol edip indirebilirsiniz.</div>
        <button class="download-btn" onclick="indirPDF()"><i class="fa-solid fa-download"></i> PDF Olarak İndir</button>
    </div>

    <div class="a4-page" id="belgeIcerik">
        
        <div class="header">
            <div class="logo-area">
                <i class="fa-solid fa-building-shield logo-icon"></i>
                <div>
                    <h1 class="company-name"><?php echo $sirket_ismi; ?></h1>
                    <p class="company-sub">Teknoloji ve Yazılım Hizmetleri</p>
                </div>
            </div>
        </div>

        <div class="doc-title">FİYAT TEKLİFİ</div>

        <div class="info-section">
            <div class="client-info">
                <h3>SAYIN / FİRMA:</h3>
                <p style="font-weight: 700; font-size: 16px;"><?php echo htmlspecialchars($b['firma_adi']); ?></p>
                <p><strong>Yetkili:</strong> <?php echo htmlspecialchars($b['yetkili_kisi'] ?? '-'); ?></p>
                <p><strong>Adres:</strong> <?php echo htmlspecialchars($b['adres'] ?? '-'); ?></p>
                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($b['telefon'] ?? '-'); ?></p>
            </div>
            
            <table class="meta-table">
                <tr><td>Tarih:</td><td><?php echo date('d.m.Y', strtotime($b['tarih'])); ?></td></tr>
                <tr><td>Teklif No:</td><td>TKLF-<?php echo str_pad($b['id'], 5, "0", STR_PAD_LEFT); ?></td></tr>
                <tr><td>Geçerlilik:</td><td><?php echo date('d.m.Y', strtotime($b['gecerlilik_tarihi'])); ?>'e kadar</td></tr>
                <tr><td>Para Birimi:</td><td><?php echo $b['para_birimi']; ?></td></tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">ÜRÜN KODU</th>
                    <th style="width: 45%;">HİZMET / ÜRÜN AÇIKLAMASI</th>
                    <th style="width: 10%;">ADET</th>
                    <th style="width: 25%; text-align: right;">BİRİM FİYAT (KDV Hariç)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                
                $kalemler = json_decode($b['detay'], true);
                
 
                if(json_last_error() !== JSON_ERROR_NONE || !is_array($kalemler)) {
                    $kalemler = [
                        ['aciklama' => $b['detay'], 'fiyat' => $b['tutar']]
                    ];
                }

                $sira = 1;
                foreach($kalemler as $kalem): 
                ?>
                <tr>
                    <td><?php echo $sira; ?></td>
                    <td>HZMT-0<?php echo $sira; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($kalem['aciklama']); ?></strong>
                    </td>
                    <td>1</td>
                    <td style="text-align: right;"><?php echo number_format($kalem['fiyat'], 2, ',', '.'); ?> <?php echo $b['para_birimi']; ?></td>
                </tr>
                <?php 
                $sira++;
                endforeach; 
                ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div class="bank-info">
                <h4>NOTLAR VE BANKA BİLGİLERİ</h4>
                <p style="margin: 3px 0;"><strong>Banka:</strong> Örnek Bankası A.Ş.</p>
                <p style="margin: 3px 0;"><strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000 00</p>
                <p style="margin: 3px 0;"><strong>Alıcı:</strong> <?php echo $sirket_ismi; ?></p>
                <br>
                <p style="margin: 3px 0; color: #4b5563;">* Ödeme %50 sipariş onayı ile peşin alınır.</p>
                <p style="margin: 3px 0; color: #4b5563;">* KDV Tutarı %20 olarak hesaplanmıştır.</p>
                <p style="margin: 3px 0; color: #4b5563;">* Bu Fiyatlar 3 iş günü için geçerlidir.</p>
            </div>
            
            <table class="totals-table">
                <tr>
                    <td>ARA TOPLAM:</td>
                    <td style="text-align: right;"><?php echo number_format($ara_toplam, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>KDV (%20):</td>
                    <td style="text-align: right;"><?php echo number_format($kdv, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>GENEL TOPLAM:</td>
                    <td style="text-align: right;"><?php echo number_format($genel_toplam, 2, ',', '.'); ?> <?php echo $b['para_birimi']; ?></td>
                </tr>
            </table>
        </div>

        <div class="signatures">
            <div class="sign-box">
                <p><?php echo mb_strtoupper($b['firma_adi']); ?></p>
                <p style="font-size: 11px; color: #6b7280;">(Müşteri Onayı)</p>
                <div class="sign-line">Tarih / Kaşe / İmza</div>
            </div>
            <div class="sign-box">
                <p><?php echo mb_strtoupper($sirket_ismi); ?></p>
                <p style="font-size: 11px; color: #6b7280;">(Hizmet Sağlayıcı)</p>
                <div class="sign-line">Kaşe / İmza</div>
            </div>
        </div>
        
    </div>

    <script>
        function indirPDF() {
            
            const btn = document.querySelector('.download-btn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> PDF Hazırlanıyor...';
            
            
            const eleman = document.getElementById('belgeIcerik');
            const secenekler = {
                margin:       0,
                filename:     'Teklif_TKLF_<?php echo $b['id']; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            
            html2pdf().set(secenekler).from(eleman).save().then(() => {
                btn.innerHTML = '<i class="fa-solid fa-download"></i> PDF Olarak İndir';
            });
        }
    </script>
</body>
</html>