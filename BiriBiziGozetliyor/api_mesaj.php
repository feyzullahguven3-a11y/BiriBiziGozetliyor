<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['personel_id'])) exit;
$benim_id = $_SESSION['personel_id'];
$islem = $_POST['islem'] ?? '';

if ($islem == 'gonder') {
    $mesaj = trim($_POST['mesaj']);
    $departman = trim($_POST['departman'] ?? '');
    $alici_id = (int)($_POST['alici_id'] ?? 0);

    if ($departman != '') {
        if ($departman == 'Tüm Şirket') {
            $kisiler = $db->query("SELECT id FROM personeller WHERE durum='aktif'")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $kisiler = $db->prepare("SELECT id FROM personeller WHERE departman = ? AND durum='aktif'");
            $kisiler->execute([$departman]);
            $kisiler = $kisiler->fetchAll(PDO::FETCH_COLUMN);
        }


        $sql = "INSERT INTO mesajlar (gonderen_id, alici_id, hedef_departman, mesaj, okundu) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        foreach ($kisiler as $k_id) {
            $okundu_mu = ($k_id == $benim_id) ? 1 : 0;
            $stmt->execute([$benim_id, $k_id, $departman, $mesaj, $okundu_mu]);
        }
        echo json_encode(['durum' => 'basarili']);
    } 
    else {
        $db->prepare("INSERT INTO mesajlar (gonderen_id, alici_id, hedef_departman, mesaj, okundu) VALUES (?, ?, NULL, ?, 0)")->execute([$benim_id, $alici_id, $mesaj]);
        echo json_encode(['durum' => 'basarili']);
    }
}
elseif ($islem == 'cek') {
    $departman = trim($_POST['departman'] ?? '');
    $alici_id = (int)($_POST['alici_id'] ?? 0);

    if ($departman != '') {

        $sql = "SELECT m.*, p.ad_soyad FROM mesajlar m 
                JOIN personeller p ON m.gonderen_id = p.id 
                WHERE m.hedef_departman = ? AND m.alici_id = ?
                ORDER BY m.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$departman, $benim_id]);
    } else {
        $sql = "SELECT m.*, p.ad_soyad FROM mesajlar m 
                JOIN personeller p ON m.gonderen_id = p.id 
                WHERE (m.hedef_departman IS NULL OR m.hedef_departman = '') 
                AND ((m.gonderen_id = ? AND m.alici_id = ?) OR (m.gonderen_id = ? AND m.alici_id = ?)) 
                ORDER BY m.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$benim_id, $alici_id, $alici_id, $benim_id]);
    }

    $mesajlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mesajlar as $m) {
        $cls = ($m['gonderen_id'] == $benim_id) ? 'ben' : 'karsi';
        $ad = ($m['gonderen_id'] == $benim_id) ? 'Sen' : htmlspecialchars($m['ad_soyad']);
        $zaman_metni = isset($m['tarih']) ? date('H:i', strtotime($m['tarih'])) : date('H:i');
        
        echo "<div class='mesaj-balonu $cls'>
                <div style='font-size:11px; margin-bottom:3px; opacity:0.8;'>$ad</div>
                <div>".htmlspecialchars($m['mesaj'])."</div>
                <div class='zaman'>$zaman_metni</div>
              </div>";
    }
}
?>