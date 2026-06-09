<?php
$host = 'localhost';
$dbname = 'biribizigozetliyor';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Gözetim Merkezi Bağlantı Hatası: " . $e->getMessage());
}
?>