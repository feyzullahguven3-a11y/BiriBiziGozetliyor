<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (isset($_SESSION['personel_id'])) {
    $benim_id = $_SESSION['personel_id'];

    $toplam = $db->query("SELECT COUNT(id) FROM mesajlar WHERE alici_id = $benim_id AND okundu = 0")->fetchColumn();

    $kisiler_q = $db->query("SELECT gonderen_id, COUNT(id) as c FROM mesajlar WHERE alici_id = $benim_id AND okundu = 0 AND (hedef_departman IS NULL OR hedef_departman = '') GROUP BY gonderen_id")->fetchAll(PDO::FETCH_ASSOC);
    $kisiler = [];
    foreach($kisiler_q as $k) { $kisiler[$k['gonderen_id']] = $k['c']; }

    $dep_q = $db->query("SELECT hedef_departman, COUNT(id) as c FROM mesajlar WHERE alici_id = $benim_id AND okundu = 0 AND hedef_departman IS NOT NULL AND hedef_departman != '' GROUP BY hedef_departman")->fetchAll(PDO::FETCH_ASSOC);
    $departmanlar = [];
    foreach($dep_q as $d) { $departmanlar[$d['hedef_departman']] = $d['c']; }

    $gorevler_sayisi = $db->query("SELECT COUNT(id) FROM gorevler WHERE atanan_id = $benim_id AND durum != 'tamamlandi'")->fetchColumn();

    echo json_encode([
        'toplam' => $toplam, 
        'kisiler' => $kisiler, 
        'departmanlar' => $departmanlar,
        'gorevler' => (int)$gorevler_sayisi // JS motoruna beslenen yeni veri
    ]);
} else {
    echo json_encode(['toplam' => 0, 'kisiler' => [], 'departmanlar' => [], 'gorevler' => 0]);
}
?>