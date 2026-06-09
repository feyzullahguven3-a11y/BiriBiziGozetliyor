<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->oturumKontrol();

$rol = $_SESSION['rol'];
$benim_id = $_SESSION['personel_id'];

$toplam_personel = $db->query("SELECT COUNT(id) FROM personeller WHERE durum='aktif'")->fetchColumn();
$toplam_musteri = $db->query("SELECT COUNT(id) FROM musteriler")->fetchColumn();
$bekleyen_izin = $db->query("SELECT COUNT(id) FROM izinler WHERE durum='bekliyor'")->fetchColumn();
$acik_destek = $db->query("SELECT COUNT(id) FROM destek_talepleri WHERE durum='açık'")->fetchColumn();

$sql_gorev = "SELECT baslik, bitis_tarihi, durum FROM gorevler WHERE atanan_id = $benim_id AND durum != 'tamamlandi' ORDER BY bitis_tarihi ASC LIMIT 5";
$benim_gorevlerim = $db->query($sql_gorev)->fetchAll(PDO::FETCH_ASSOC);

$canli_akis = [];
if ($rol == 'yonetici' || $rol == 'it') {
    try {
        $sql_log = "SELECT l.islem_turu, l.detay, l.tarih, p.ad_soyad 
                    FROM loglar l 
                    LEFT JOIN personeller p ON l.personel_id = p.id 
                    ORDER BY l.id DESC LIMIT 8";
        $canli_akis = $db->query($sql_log)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $canli_akis = [];
    }
}

$kabul = 0; $red = 0; $bekleyen = 0;
if ($rol == 'yonetici' || $rol == 'it' || $rol == 'satis') {
    try {
        $pie_q = $db->query("SELECT durum, COUNT(*) as c FROM teklifler GROUP BY durum")->fetchAll(PDO::FETCH_ASSOC);
        foreach($pie_q as $row) {
            if($row['durum'] == 'kabul_edildi') $kabul = $row['c'];
            if($row['durum'] == 'reddedildi') $red = $row['c'];
            if($row['durum'] == 'bekliyor') $bekleyen = $row['c'];
        }
    } catch (Exception $e) {
    }
}

include 'header.php';
?>

    <h2><i class="fa-solid fa-house" style="color: var(--primary-color);"></i> Kontrol Paneli</h2>
    <p style="color: var(--text-muted); margin-bottom: 25px;">Sistemin genel durumuna ve aktif işlerinize hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong>.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card" style="margin: 0; display: flex; align-items: center; gap: 15px; border-left: 4px solid #4f46e5;">
            <div style="background: rgba(79, 70, 229, 0.1); padding: 15px; border-radius: 10px; color: #4f46e5; font-size: 24px;"><i class="fa-solid fa-users"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Aktif Personel</h4>
                <div style="font-size: 24px; font-weight: 800; color: var(--text-main);"><?php echo $toplam_personel; ?></div>
            </div>
        </div>

        <div class="card" style="margin: 0; display: flex; align-items: center; gap: 15px; border-left: 4px solid #10b981;">
            <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 10px; color: #10b981; font-size: 24px;"><i class="fa-solid fa-building"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Kayıtlı Firma</h4>
                <div style="font-size: 24px; font-weight: 800; color: var(--text-main);"><?php echo $toplam_musteri; ?></div>
            </div>
        </div>

        <div class="card" style="margin: 0; display: flex; align-items: center; gap: 15px; border-left: 4px solid #f59e0b;">
            <div style="background: rgba(245, 158, 11, 0.1); padding: 15px; border-radius: 10px; color: #f59e0b; font-size: 24px;"><i class="fa-solid fa-calendar-minus"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Bekleyen İzin</h4>
                <div style="font-size: 24px; font-weight: 800; color: var(--text-main);"><?php echo $bekleyen_izin; ?></div>
            </div>
        </div>

        <div class="card" style="margin: 0; display: flex; align-items: center; gap: 15px; border-left: 4px solid #ef4444;">
            <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 10px; color: #ef4444; font-size: 24px;"><i class="fa-solid fa-headset"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Açık Destek</h4>
                <div style="font-size: 24px; font-weight: 800; color: var(--text-main);"><?php echo $acik_destek; ?></div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
        
        <div class="card" style="margin: 0;">
            <h3 style="margin-top: 0;"><i class="fa-solid fa-list-check" style="color: var(--primary-color);"></i> Bana Atanan Görevler</h3>
            
            <?php if(empty($benim_gorevlerim)): ?>
                <div style="text-align: center; padding: 30px 0; color: var(--text-muted);">
                    <i class="fa-solid fa-mug-hot" style="font-size: 32px; margin-bottom: 10px; color: #d1d5db;"></i>
                    <p>Harika! Bekleyen hiçbir göreviniz yok.</p>
                </div>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 15px 0 0 0;">
                    <?php foreach($benim_gorevlerim as $g): ?>
                        <li style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: var(--text-main); font-size: 14px;"><?php echo htmlspecialchars($g['baslik']); ?></strong><br>
                                <span style="font-size: 12px; color: <?php echo (strtotime($g['bitis_tarihi']) < time()) ? 'var(--danger-color); font-weight:bold;' : 'var(--text-muted);'; ?>">
                                    <i class="fa-regular fa-clock"></i> Son: <?php echo date('d.m.Y', strtotime($g['bitis_tarihi'])); ?>
                                </span>
                            </div>
                            <span class="badge <?php echo ($g['durum'] == 'islemde') ? 'bg-islemde' : 'bg-red'; ?>" style="font-size: 11px;">
                                <?php echo ($g['durum'] == 'islemde') ? 'İşlemde' : 'Yapılacak'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ($rol == 'yonetici' || $rol == 'it' || $rol == 'satis'): ?>
        <div class="card" style="margin: 0; display: flex; flex-direction: column;">
            <h3 style="margin-top: 0;"><i class="fa-solid fa-chart-pie" style="color: #f59e0b;"></i> Teklif Başarı Oranları</h3>
            <div style="position: relative; height: 250px; width: 100%; display: flex; justify-content: center; align-items: center; margin-top: auto; margin-bottom: auto;">
                <?php if($kabul == 0 && $red == 0 && $bekleyen == 0): ?>
                    <p style="color: var(--text-muted); font-size: 14px;">Henüz sistemde veri bulunmuyor.</p>
                <?php else: ?>
                    <canvas id="teklifGrafigi"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($rol == 'yonetici' || $rol == 'it'): ?>
        <div class="card" style="margin: 0;">
            <h3 style="margin-top: 0; display: flex; justify-content: space-between;">
                <span><i class="fa-solid fa-satellite-dish" style="color: var(--danger-color);"></i> Canlı Sistem Akışı</span>
                <span style="font-size: 11px; background: rgba(239, 68, 68, 0.1); color: var(--danger-color); padding: 4px 8px; border-radius: 4px; font-weight: bold;">Canlı</span>
            </h3>
            
            <?php if(empty($canli_akis)): ?>
                <div style="text-align: center; padding: 30px 0; color: var(--text-muted);">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 32px; margin-bottom: 10px; color: #d1d5db;"></i>
                    <p>Sistemde henüz kayıtlı bir hareket yok.</p>
                </div>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 15px 0 0 0;">
                    <?php foreach($canli_akis as $akis): ?>
                        <li style="padding: 10px 0; border-bottom: 1px dashed var(--border-color); font-size: 13px;">
                            <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($akis['ad_soyad'] ?? 'Sistem'); ?>:</strong> 
                            <span style="color: var(--text-main);"><?php echo htmlspecialchars($akis['detay']); ?></span><br>
                            <span style="font-size: 11px; color: var(--text-muted);"><i class="fa-regular fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($akis['tarih'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="loglar.php" style="font-size: 12px; color: var(--primary-color); text-decoration: none; font-weight: 600;">Tüm Logları Gör &rarr;</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

<?php 
include 'footer.php'; 
?>

<?php if (($rol == 'yonetici' || $rol == 'it' || $rol == 'satis') && ($kabul > 0 || $red > 0 || $bekleyen > 0)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('teklifGrafigi').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Kabul Edilen', 'Reddedilen', 'Onay Bekleyen'],
                    datasets: [{
                        data: [<?php echo $kabul; ?>, <?php echo $red; ?>, <?php echo $bekleyen; ?>],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { 
                            position: 'bottom', 
                            labels: { 
                                font: { family: 'Inter', size: 12 },
                                padding: 20
                            } 
                        }
                    }
                }
            });
        });
    </script>
<?php endif; ?>