<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'satis']); 

if (!isset($_GET['id'])) {
    header("Location: musteriler.php");
    exit;
}

$musteri_id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT * FROM musteriler WHERE id = ?");
$stmt->execute([$musteri_id]);
$musteri = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$musteri) { die("Müşteri bulunamadı."); }

$destekler = $db->query("SELECT * FROM destek_talepleri WHERE musteri_id = $musteri_id ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$teklifler = $db->query("SELECT * FROM teklifler WHERE musteri_id = $musteri_id ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($musteri['firma_adi']); ?> - Müşteri 360°</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        
        /* Layout Grid */
        .layout-grid { display: grid; grid-template-columns: 350px 1fr; gap: 20px; align-items: start; }
        @media (max-width: 1024px) { .layout-grid { grid-template-columns: 1fr; } }
        
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);}
        
        /* Sol Profil Kartı */
        .profile-icon { width: 80px; height: 80px; background: rgba(79, 70, 229, 0.1); color: var(--primary-color); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; }
        .info-row { display: flex; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0;}
        .info-icon { color: var(--text-muted); font-size: 18px; width: 25px; text-align: center;}
        
        /* Sağ Tablolar */
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; display: inline-flex;}
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        th { color: var(--text-muted); text-transform: uppercase; font-size: 11px; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .bg-acik, .bg-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .bg-islemde, .bg-bekliyor { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .bg-cozuldu, .bg-kabul { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        
        .btn-back { display: inline-block; padding: 10px 15px; background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: 0.2s;}
        .btn-back:hover { background: var(--bg-color); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <a href="musteriler.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Müşterilere Dön</a>
        
        <div class="layout-grid">
            
            <div class="card">
                <div class="profile-icon"><i class="fa-solid fa-building"></i></div>
                <h2 style="font-size: 22px; margin-bottom: 5px;"><?php echo htmlspecialchars($musteri['firma_adi']); ?></h2>
                <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 14px;">Müşteri 360° Profili</p>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Yetkili Kişi</div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($musteri['yetkili_kisi'] ?? 'Belirtilmedi'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">E-Posta</div>
                        <div style="font-weight: 600; font-size: 14px;">
                            <a href="mailto:<?php echo htmlspecialchars($musteri['email'] ?? ''); ?>" style="color: var(--primary-color); text-decoration: none;">
                                <?php echo htmlspecialchars($musteri['email'] ?? 'Belirtilmedi'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Telefon</div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($musteri['telefon'] ?? 'Belirtilmedi'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Açık Adres</div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($musteri['adres'] ?? 'Belirtilmedi'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Kayıt Tarihi</div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo date('d.m.Y', strtotime($musteri['kayit_tarihi'])); ?></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="section-title"><i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary-color);"></i> Sunulan Teklifler</div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead><tr><th>Tarih</th><th>Konu</th><th>Tutar</th><th>Durum</th></tr></thead>
                            <tbody>
                                <?php foreach($teklifler as $t): 
                                    $cls = ''; $txt = '';
                                    if($t['durum'] == 'bekliyor') { $cls='bg-bekliyor'; $txt='Bekliyor'; }
                                    if($t['durum'] == 'kabul_edildi') { $cls='bg-kabul'; $txt='Kabul Edildi'; }
                                    if($t['durum'] == 'reddedildi') { $cls='bg-red'; $txt='Reddedildi'; }
                                ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($t['tarih'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['konu']); ?></strong></td>
                                    <td><?php echo number_format($t['tutar'], 2, ',', '.') . " " . $t['para_birimi']; ?></td>
                                    <td><span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($teklifler)) echo "<tr><td colspan='4' style='text-align:center; color:var(--text-muted);'>Bu müşteriye henüz teklif sunulmamış.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="section-title"><i class="fa-solid fa-headset" style="color: var(--primary-color);"></i> Destek / Arıza Talepleri</div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead><tr><th>Tarih</th><th>Konu / Mesaj</th><th>Öncelik</th><th>Durum</th></tr></thead>
                            <tbody>
                                <?php foreach($destekler as $d): 
                                    $cls = '';
                                    if($d['durum'] == 'açık') $cls='bg-acik'; 
                                    if($d['durum'] == 'işlemde') $cls='bg-islemde'; 
                                    if($d['durum'] == 'çözüldü') $cls='bg-cozuldu'; 
                                ?>
                                <tr>
                                    <td style="white-space: nowrap;"><?php echo date('d.m.Y H:i', strtotime($d['tarih'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($d['konu']); ?></strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?php echo mb_substr(htmlspecialchars($d['mesaj']), 0, 50) . "..."; ?></span>
                                    </td>
                                    <td><?php echo strtoupper($d['oncelik']); ?></td>
                                    <td><span class="badge <?php echo $cls; ?>"><?php echo $d['durum']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($destekler)) echo "<tr><td colspan='4' style='text-align:center; color:var(--text-muted);'>Bu müşterinin geçmiş bir destek talebi bulunmuyor.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>