<?php

session_start();
require_once 'config/db.php';
require_once 'classes/Gozetmen.php';

if (isset($_SESSION['personel_id'])) {
    $gozetmen = new Gozetmen($db);
    $gozetmen->logTut($_SESSION['personel_id'], 'sistem_cikis', 'Kullanıcı sistemden güvenli çıkış yaptı.');
}

session_destroy();
header("Location: login.php");
exit;
?>