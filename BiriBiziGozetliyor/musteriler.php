<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'satis']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['musteri_ekle'])) {
    $firma_adi = trim($_POST['firma_adi']);
    $yetkili = trim($_POST['yetkili_kisi']);
    $telefon = trim($_POST['telefon']);
    $email = trim($_POST['email']);
    $adres = trim($_POST['adres']);
    $ekleyen_id = $_SESSION['personel_id'];
    
    try {
        $sql = "INSERT INTO musteriler (firma_adi, yetkili_kisi, telefon, email, adres, ekleyen_personel_id) VALUES (?, ?, ?, ?, ?, ?)";
        if ($db->prepare($sql)->execute([$firma_adi, $yetkili, $telefon, $email, $adres, $ekleyen_id])) {
            $gozetmen->logTut($ekleyen_id, 'musteri_ekledi', "'$firma_adi' isimli yeni bir müşteri ekledi.");
            $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Müşteri başarıyla eklendi!</div>";
        }
    } catch(PDOException $e) {
        $mesaj = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Bir hata oluştu!</div>";
    }
}

$stmt = $db->query("SELECT m.*, p.ad_soyad as ekleyen_kisi FROM musteriler m LEFT JOIN personeller p ON m.ekleyen_personel_id = p.id ORDER BY m.id DESC");
$musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php'; 
?>

    <h2><i class="fa-solid fa-building" style="color: var(--primary-color);"></i> CRM - Müşteri Yönetimi</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Yeni müşteri ekleyin ve mevcut müşterileri yönetin.</p>

    <?php echo $mesaj; ?>

    <div class="card">
        <h3>Yeni Müşteri Ekle</h3>
        <form method="POST" action="musteriler.php" style="margin-top: 15px;">
            <div class="form-row">
                <div class="form-group"><label>Firma Adı</label><input type="text" name="firma_adi" required></div>
                <div class="form-group"><label>Yetkili Kişi</label><input type="text" name="yetkili_kisi"></div>
                <div class="form-group"><label>Telefon</label><input type="text" name="telefon"></div>
                <div class="form-group"><label>E-Posta</label><input type="email" name="email"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Firma Açık Adresi</label><input type="text" name="adres"></div>
            </div>
            <button type="submit" name="musteri_ekle" class="btn"><i class="fa-solid fa-plus"></i> Kaydet</button>
        </form>
    </div>

    <div class="card">
        <h3>Kayıtlı Müşteriler</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Firma Adı</th>
                        <th>Yetkili</th>
                        <th>Telefon</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($musteriler as $m): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($m['firma_adi']); ?></strong></td>
                        <td><?php echo htmlspecialchars($m['yetkili_kisi'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($m['telefon'] ?? '-'); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($m['kayit_tarihi'])); ?></td>
                        <td>
                            <a href="musteri_detay.php?id=<?php echo $m['id']; ?>" class="btn btn-sm"><i class="fa-solid fa-eye"></i> 360° İncele</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php 
include 'footer.php'; 
?>