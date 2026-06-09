<?php
session_start();
require_once 'config/db.php';

if(isset($_GET['id']) && isset($_SESSION['personel_id'])) {
    $id = (int)$_GET['id'];
    $link = isset($_GET['link']) && !empty($_GET['link']) ? $_GET['link'] : 'dashboard.php';
    
    $db->query("UPDATE bildirimler SET okundu_mu = 1 WHERE id = $id AND personel_id = " . $_SESSION['personel_id']);
    
    header("Location: " . $link);
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}
?>