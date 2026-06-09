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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gorev_ekle'])) {
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $bitis_tarihi = $_POST['bitis_tarihi'];
    
    $atanan_kisi = isset($_POST['atanan_id']) ? $_POST['atanan_id'] : $benim_id;

    $sql = "INSERT INTO gorevler (baslik, aciklama, atayan_id, atanan_id, bitis_tarihi, durum) VALUES (?, ?, ?, ?, ?, 'bekliyor')";
    if ($db->prepare($sql)->execute([$baslik, $aciklama, $benim_id, $atanan_kisi, $bitis_tarihi])) {
        $gozetmen->logTut($benim_id, 'gorev_olusturdu', "'$baslik' isimli görevi oluşturdu.");
        

        if($atanan_kisi != $benim_id) {
            try {
                $bildirim_sql = "INSERT INTO bildirimler (personel_id, baslik, icerik, link) VALUES (?, ?, ?, ?)";
                $db->prepare($bildirim_sql)->execute([$atanan_kisi, 'Yeni Görev Atandı', "Size yeni bir görev atandı: $baslik", 'gorevler.php']);
            } catch (Exception $e) {
            }
        }
        
        $mesaj = "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Görev başarıyla oluşturuldu.', showConfirmButton: false, timer: 3000 });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    $gorev_id = $_POST['gorev_id'];
    $yeni_durum = $_POST['yeni_durum'];

    $db->prepare("UPDATE gorevler SET durum = ? WHERE id = ?")->execute([$yeni_durum, $gorev_id]);
    $gozetmen->logTut($benim_id, 'gorev_guncelledi', "Görev ID: $gorev_id durumunu '$yeni_durum' yaptı.");
    
    $mesaj = "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Görev durumu güncellendi', showConfirmButton: false, timer: 3000 });</script>";
}

if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $db->prepare("DELETE FROM gorevler WHERE id = ?")->execute([$sil_id]);
    $gozetmen->logTut($benim_id, 'gorev_silindi', "Görev ID: $sil_id silindi.");
    header("Location: gorevler.php?durum=silindi");
    exit;
}
if(isset($_GET['durum']) && $_GET['durum'] == 'silindi') {
    $mesaj = "<script>Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Görev sistemden silindi', showConfirmButton: false, timer: 3000 });</script>";
}

$personeller = $db->query("SELECT id, ad_soyad FROM personeller WHERE durum='aktif' ORDER BY ad_soyad")->fetchAll(PDO::FETCH_ASSOC);

if ($rol == 'yonetici' || $rol == 'ik') {
    // Yönetici herkesin görevini görür
    $sql = "SELECT g.*, p_atayan.ad_soyad as atayan_ad, p_atanan.ad_soyad as atanan_ad 
            FROM gorevler g 
            LEFT JOIN personeller p_atayan ON g.atayan_id = p_atayan.id 
            LEFT JOIN personeller p_atanan ON g.atanan_id = p_atanan.id 
            ORDER BY g.id DESC";
} else {
    $sql = "SELECT g.*, p_atayan.ad_soyad as atayan_ad, p_atanan.ad_soyad as atanan_ad 
            FROM gorevler g 
            LEFT JOIN personeller p_atayan ON g.atayan_id = p_atayan.id 
            LEFT JOIN personeller p_atanan ON g.atanan_id = p_atanan.id 
            WHERE g.atanan_id = $benim_id OR g.atayan_id = $benim_id 
            ORDER BY g.id DESC";
}
$gorevler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-list-check" style="color: var(--primary-color);"></i> Görev Yönetimi</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">İş akışınızı planlayın, görev atayın ve süreçleri takip edin.</p>

    <?php echo $mesaj; ?>

    <div class="card">
        <h3><i class="fa-solid fa-plus"></i> Yeni Görev Oluştur</h3>
        <form method="POST" style="margin-top: 15px;">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label>Görev Başlığı</label>
                    <input type="text" name="baslik" required placeholder="Örn: Veritabanı Yedeklemesi">
                </div>
                
                <?php if($rol == 'yonetici' || $rol == 'ik' || $rol == 'it'): ?>
                <div class="form-group" style="flex: 1;">
                    <label>Kime Atanacak?</label>
                    <select name="atanan_id" required>
                        <option value="<?php echo $benim_id; ?>">Kendime (Kişisel Not)</option>
                        <optgroup label="Diğer Çalışanlar">
                            <?php foreach($personeller as $p): if($p['id'] != $benim_id): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['ad_soyad']); ?></option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group" style="flex: 1;">
                    <label>Son Teslim Tarihi</label>
                    <input type="date" name="bitis_tarihi" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Görev Detayı / Açıklama</label>
                    <textarea name="aciklama" rows="2" placeholder="Yapılacak işin detayları..." required></textarea>
                </div>
            </div>

            <button type="submit" name="gorev_ekle" class="btn"><i class="fa-solid fa-paper-plane"></i> Görevi Kaydet & Ata</button>
        </form>
    </div>

    <div class="card">
        <h3>Görev Listesi</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Görev Adı & Detay</th>
                        <th>Atayan</th>
                        <th>Sorumlu Kişi</th>
                        <th>Teslim Tarihi</th>
                        <th>Durum</th>
                        <th style="width: 120px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gorevler as $g): 
                        $cls = ''; $txt = '';
                        if($g['durum'] == 'bekliyor') { $cls='bg-red'; $txt='Yapılacak'; }
                        if($g['durum'] == 'islemde') { $cls='bg-islemde'; $txt='İşlemde / Devam Ediyor'; }
                        if($g['durum'] == 'tamamlandi') { $cls='bg-cozuldu'; $txt='Tamamlandı'; }
                        
                        $tarih_gecti_mi = (strtotime($g['bitis_tarihi']) < strtotime(date('Y-m-d')) && $g['durum'] != 'tamamlandi') ? "color: var(--danger-color); font-weight: bold;" : "";
                        
                        // Detayları güvenli hale getiriyoruz (JavaScript ve HTML için)
                        $safe_baslik = htmlspecialchars($g['baslik'], ENT_QUOTES, 'UTF-8');
                        $safe_aciklama = htmlspecialchars($g['aciklama'], ENT_QUOTES, 'UTF-8');
                        $kisa_aciklama = mb_strlen($g['aciklama']) > 50 ? mb_substr(htmlspecialchars($g['aciklama']), 0, 50) . '...' : htmlspecialchars($g['aciklama']);
                    ?>
                    <tr style="<?php echo ($g['durum'] == 'tamamlandi') ? 'opacity: 0.6;' : ''; ?>">
                        
                        <td style="cursor: pointer;" onclick="gorevDetayGoster(this)" data-baslik="<?php echo $safe_baslik; ?>" data-aciklama="<?php echo $safe_aciklama; ?>" title="Tüm detayları okumak için tıklayın">
                            <strong><?php echo $safe_baslik; ?></strong><br>
                            <span style="font-size: 11px; color: var(--text-muted);"><?php echo $kisa_aciklama; ?></span>
                            <div style="margin-top: 5px;">
                                <span style="font-size: 10px; background: rgba(79,70,229,0.1); color: var(--primary-color); padding: 3px 6px; border-radius: 4px; font-weight: bold;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Oku
                                </span>
                            </div>
                        </td>
                        
                        <td><i class="fa-solid fa-user-pen" style="font-size:11px; color:var(--text-muted);"></i> <?php echo htmlspecialchars($g['atayan_ad']); ?></td>
                        <td><strong><i class="fa-solid fa-user-check" style="font-size:11px; color:var(--primary-color);"></i> <?php echo htmlspecialchars($g['atanan_ad']); ?></strong></td>
                        <td style="<?php echo $tarih_gecti_mi; ?>"><i class="fa-regular fa-calendar"></i> <?php echo date('d.m.Y', strtotime($g['bitis_tarihi'])); ?></td>
                        <td><span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span></td>
                        <td style="display: flex; gap: 5px;">
                            <form method="POST" style="margin:0; padding:0;">
                                <input type="hidden" name="gorev_id" value="<?php echo $g['id']; ?>">
                                <input type="hidden" name="durum_guncelle" value="1">
                                <select name="yeni_durum" onchange="this.form.submit()" style="padding: 6px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border-color); outline:none; cursor:pointer;">
                                    <option value="bekliyor" <?php echo ($g['durum']=='bekliyor')?'selected':''; ?>>Yapılacak</option>
                                    <option value="islemde" <?php echo ($g['durum']=='islemde')?'selected':''; ?>>İşlemde</option>
                                    <option value="tamamlandi" <?php echo ($g['durum']=='tamamlandi')?'selected':''; ?>>Tamamlandı</option>
                                </select>
                            </form>
                            
                            <?php if($rol == 'yonetici' || $g['atayan_id'] == $benim_id): ?>
                                <a href="#" onclick="event.preventDefault(); Swal.fire({title: 'Emin misiniz?', text: 'Bu görev kalıcı olarak silinecek!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'}).then((result) => { if (result.isConfirmed) { window.location.href = '?sil=<?php echo $g['id']; ?>'; } })" class="action-btn btn-danger" style="padding: 6px 10px;"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function gorevDetayGoster(element) {
            const baslik = element.getAttribute('data-baslik');
            let aciklama = element.getAttribute('data-aciklama');
            

            aciklama = aciklama.replace(/\n/g, '<br>');
            
            Swal.fire({
                title: '<strong style="color: var(--primary-color); font-size: 20px;">' + baslik + '</strong>',
                html: '<div style="text-align: left; font-size: 14px; line-height: 1.6; max-height: 400px; overflow-y: auto;">' + aciklama + '</div>',
                showCloseButton: true,
                focusConfirm: false,
                confirmButtonText: '<i class="fa-solid fa-check"></i> Kapat',
                confirmButtonColor: '#4f46e5',
                width: '600px',
                background: 'var(--card-bg)', 
                color: 'var(--text-main)'     
            });
        }
    </script>

<?php include 'footer.php'; ?>