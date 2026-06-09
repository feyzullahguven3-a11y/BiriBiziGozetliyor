<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'ik', 'it']); 

$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['departman_ekle'])) {
    $ad = trim($_POST['departman_adi']);
    try {
        $db->prepare("INSERT INTO departmanlar (ad) VALUES (?)")->execute([$ad]);
        $mesaj = "<div class='alert success'><i class='fa-solid fa-check'></i> Departman eklendi.</div>";
    } catch(PDOException $e) {
        $mesaj = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Bu departman zaten mevcut!</div>";
    }
}

if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $db->prepare("DELETE FROM departmanlar WHERE id = ?")->execute([$sil_id]);
    header("Location: departmanlar.php");
    exit;
}

$departmanlar = $db->query("SELECT * FROM departmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Departman Yönetimi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .btn { padding: 10px 15px; background: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        
        .btn-action { padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; color: white; display: inline-block; margin-right: 5px;}
        .bg-red { background: #ef4444; }
        .bg-dark { background: #374151; }
        
        input { padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; width: 300px; background: var(--bg-color); color: var(--text-main); outline: none; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; } .error { background: rgba(239,68,68,0.1); color: #ef4444; }
        form { display: flex; gap: 10px; align-items: center; margin-top: 15px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <h2><i class="fa-solid fa-sitemap" style="color: var(--primary-color);"></i> Departman Yönetimi</h2>
        <?php echo $mesaj; ?>
        
        <div class="card">
            <h3>Yeni Departman Ekle</h3>
            <form method="POST">
                <input type="text" name="departman_adi" required placeholder="Örn: AR-GE, Muhasebe...">
                <button type="submit" name="departman_ekle" class="btn"><i class="fa-solid fa-plus"></i> Ekle</button>
            </form>
        </div>

        <div class="card">
            <h3>Mevcut Departmanlar</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Departman Adı</th>
                            <th style="width: 250px;">Hızlı İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($departmanlar as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['ad']); ?></strong></td>
                            <td>
                                <a href="mesajlar.php?departman=<?php echo urlencode($d['ad']); ?>" class="btn-action bg-dark"><i class="fa-solid fa-bullhorn"></i> Duyuru Yap</a>
                                
                                <a href="?sil=<?php echo $d['id']; ?>" class="btn-action bg-red" onclick="return confirm('Silmek istediğinize emin misiniz?');"><i class="fa-solid fa-trash"></i> Sil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>