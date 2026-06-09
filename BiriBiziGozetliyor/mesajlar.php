<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->oturumKontrol();

$benim_id = $_SESSION['personel_id'];
$rol = $_SESSION['rol'];

$benim_departmanim = $db->query("SELECT departman FROM personeller WHERE id = $benim_id")->fetchColumn();


if ($rol == 'yonetici') {
    $kanallar = $db->query("SELECT ad as departman FROM departmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
} else {

    $kanallar = !empty($benim_departmanim) ? [['departman' => $benim_departmanim]] : [];
}

$personeller = $db->query("SELECT id, ad_soyad, departman FROM personeller WHERE id != $benim_id AND durum='aktif' ORDER BY ad_soyad ASC")->fetchAll(PDO::FETCH_ASSOC);

$secili_kisi_id = isset($_GET['kim']) ? (int)$_GET['kim'] : 0;
$secili_departman = isset($_GET['departman']) ? trim($_GET['departman']) : '';


if ($secili_departman != '') {
    $db->prepare("UPDATE mesajlar SET okundu = 1 WHERE alici_id = ? AND hedef_departman = ? AND okundu = 0")
       ->execute([$benim_id, $secili_departman]);
} else if ($secili_kisi_id > 0) {
    $db->prepare("UPDATE mesajlar SET okundu = 1 WHERE alici_id = ? AND (hedef_departman IS NULL OR hedef_departman = '') AND gonderen_id = ? AND okundu = 0")
       ->execute([$benim_id, $secili_kisi_id]);
}

$sohbet_basligi = "Bir sohbet seçin";
$sohbet_ikonu = "fa-comments";

if ($secili_departman == 'Tüm Şirket') {
    $sohbet_basligi = "🌍 Genel Şirket Duyuruları";
    $sohbet_ikonu = "fa-earth-europe";
} else if ($secili_departman != '') {
    $sohbet_basligi = "📢 " . htmlspecialchars($secili_departman) . " Ortak Grubu";
    $sohbet_ikonu = "fa-bullhorn";
} else if ($secili_kisi_id > 0) {
    $kisi_ad = $db->query("SELECT ad_soyad FROM personeller WHERE id = $secili_kisi_id")->fetchColumn();
    if ($kisi_ad) {
        $sohbet_basligi = htmlspecialchars($kisi_ad);
        $sohbet_ikonu = "fa-user-circle";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İç İletişim & Duyurular</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #f3f4f6; --card-bg: #ffffff; --text-main: #1f2937; --text-muted: #6b7280; --primary-color: #4f46e5; --border-color: #e5e7eb; }
        @media (prefers-color-scheme: dark) { :root { --bg-color: #111827; --card-bg: #1f2937; --text-main: #f9fafb; --border-color: #374151; } }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }
        
        .main-content { flex: 1; padding: 20px; display: flex; flex-direction: column; }
        .chat-container { display: flex; flex: 1; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; margin-top: 15px; }
        
        .chat-sidebar { width: 300px; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: var(--card-bg); overflow-y: auto;}
        .kategori-baslik { padding: 15px; font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); background: var(--bg-color); border-bottom: 1px solid var(--border-color); border-top: 1px solid var(--border-color);}
        .kategori-baslik:first-child { border-top: none; }
        
        .kisi-item { padding: 12px 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); text-decoration: none; transition: 0.2s; position: relative;}
        .kisi-item:hover, .kisi-item.active { background: rgba(79, 70, 229, 0.1); border-left: 3px solid var(--primary-color); padding-left: 12px;}
        .kisi-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;}
        .kanal-avatar { background: #10b981; }
        .genel-avatar { background: #ef4444; } 
        
        .item-badge { background: var(--danger-color); color: white; padding: 2px 7px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: auto; display: none; box-shadow: 0 0 5px rgba(239,68,68,0.3);}

        .chat-area { flex: 1; display: flex; flex-direction: column; background: var(--bg-color); }
        .chat-header { padding: 15px 20px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .mesaj-gecmisi { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        
        .mesaj-balonu { max-width: 60%; padding: 10px 15px; border-radius: 15px; font-size: 14px; position: relative; }
        .mesaj-balonu .zaman { font-size: 10px; opacity: 0.7; margin-top: 5px; text-align: right; }
        .mesaj-balonu.ben { background: var(--primary-color); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .mesaj-balonu.karsi { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); align-self: flex-start; border-bottom-left-radius: 2px; }
        
        .mesaj-yazma-alani { padding: 15px; background: var(--card-bg); border-top: 1px solid var(--border-color); display: flex; gap: 10px; }
        .mesaj-yazma-alani input { flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 20px; background: var(--bg-color); color: var(--text-main); outline: none; }
        .btn-gonder { background: var(--primary-color); color: white; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-gonder:hover { background: #4338ca; }
        .bos-durum { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2><i class="fa-solid fa-comments" style="color: var(--primary-color);"></i> İç İletişim & Duyurular</h2>
        
        <div class="chat-container">
            <div class="chat-sidebar">
                
                <div class="kategori-baslik">Genel Şirket Ağı</div>
                <a href="mesajlar.php?departman=Tüm Şirket" class="kisi-item <?php echo ($secili_departman == 'Tüm Şirket') ? 'active' : ''; ?>">
                    <div class="kisi-avatar genel-avatar"><i class="fa-solid fa-earth-europe"></i></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;">📢 Tüm Şirket</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Genel Duyuru Kanalı</div>
                    </div>
                    <span class="item-badge" data-tip="dep" data-id="Tüm Şirket"></span>
                </a>

                <div class="kategori-baslik">Ortak Grup</div>
                <?php foreach($kanallar as $k): 
                    $aktif_class = ($secili_departman == $k['departman']) ? 'active' : '';
                ?>
                <a href="mesajlar.php?departman=<?php echo urlencode($k['departman']); ?>" class="kisi-item <?php echo $aktif_class; ?>">
                    <div class="kisi-avatar kanal-avatar"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($k['departman']); ?></div>
                        <div style="font-size: 11px; color: var(--text-muted);">Ortak Çalışma Grubu</div>
                    </div>
                    <span class="item-badge" data-tip="dep" data-id="<?php echo htmlspecialchars($k['departman']); ?>"></span>
                </a>
                <?php endforeach; ?>

                <div class="kategori-baslik">Çalışma Arkadaşları</div>
                <?php foreach($personeller as $p): 
                    $aktif_class = ($secili_kisi_id == $p['id']) ? 'active' : '';
                    $bas_harf = mb_substr($p['ad_soyad'], 0, 1);
                ?>
                <a href="mesajlar.php?kim=<?php echo $p['id']; ?>" class="kisi-item <?php echo $aktif_class; ?>">
                    <div class="kisi-avatar"><?php echo $bas_harf; ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px"><?php echo htmlspecialchars($p['ad_soyad']); ?></div>
                        <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($p['departman']); ?></div>
                    </div>
                    <span class="item-badge" data-tip="kisi" data-id="<?php echo $p['id']; ?>"></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="chat-area">
                <?php if($secili_kisi_id > 0 || $secili_departman != ''): ?>
                    <div class="chat-header">
                        <i class="fa-solid <?php echo $sohbet_ikonu; ?>" style="font-size: 24px; color: var(--primary-color);"></i>
                        <?php echo $sohbet_basligi; ?>
                    </div>
                    
                    <div class="mesaj-gecmisi" id="mesajKutusu"></div>
                    
                    <?php if($secili_departman == 'Tüm Şirket' && $rol != 'yonetici'): ?>
                        <div style="padding: 15px; text-align: center; background: var(--bg-color); border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 13px;">
                            <i class="fa-solid fa-lock"></i> Bu kanalda sadece Sistem Yöneticileri duyuru yapabilir.
                        </div>
                    <?php else: ?>
                        <form class="mesaj-yazma-alani" id="mesajFormu">
                            <input type="hidden" id="alici_id" value="<?php echo $secili_kisi_id; ?>">
                            <input type="hidden" id="hedef_departman" value="<?php echo htmlspecialchars($secili_departman); ?>">
                            <input type="text" id="mesajGirdi" placeholder="<?php echo ($secili_departman != '') ? 'Duyuru yapın...' : 'Mesajınızı yazın...'; ?>" required autocomplete="off">
                            <button type="submit" class="btn-gonder"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bos-durum">
                        <i class="fa-regular fa-comments" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h3>Sohbete Başlayın</h3>
                        <p>Duyuru yapmak veya mesajlaşmak için sol taraftan seçim yapın.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function guncelleListeRozetleri(kisiler, departmanlar) {
            document.querySelectorAll('.item-badge').forEach(b => b.style.display = 'none');
            
            document.querySelectorAll('.item-badge[data-tip="kisi"]').forEach(b => {
                let id = b.getAttribute('data-id');
                if (kisiler[id] > 0) { 
                    b.style.display = 'inline-block'; 
                    b.innerText = kisiler[id]; 
                }
            });

            document.querySelectorAll('.item-badge[data-tip="dep"]').forEach(b => {
                let ad = b.getAttribute('data-id');
                if (departmanlar[ad] > 0) { 
                    b.style.display = 'inline-block'; 
                    b.innerText = departmanlar[ad]; 
                }
            });
        }

        <?php if($secili_kisi_id > 0 || $secili_departman != ''): ?>
        const aliciId = document.getElementById('alici_id') ? document.getElementById('alici_id').value : 0;
        const hedefDepartman = document.getElementById('hedef_departman') ? document.getElementById('hedef_departman').value : '<?php echo htmlspecialchars($secili_departman); ?>';
        const mesajKutusu = document.getElementById('mesajKutusu');
        const mesajFormu = document.getElementById('mesajFormu');
        const mesajGirdi = document.getElementById('mesajGirdi');

        function mesajlariGetir() {
            let data = new URLSearchParams();
            data.append('islem', 'cek');
            if(hedefDepartman !== '') data.append('departman', hedefDepartman);
            else data.append('alici_id', aliciId);

            fetch('api_mesaj.php', { method: 'POST', body: data })
            .then(response => response.text())
            .then(html => {
                if (mesajKutusu.innerHTML !== html) {
                    mesajKutusu.innerHTML = html;
                    mesajKutusu.scrollTop = mesajKutusu.scrollHeight;
                }
            });
        }

        if (mesajFormu) {
            mesajFormu.addEventListener('submit', function(e) {
                e.preventDefault();
                const mesaj = mesajGirdi.value;
                if(mesaj.trim() === '') return;

                let data = new URLSearchParams();
                data.append('islem', 'gonder');
                data.append('mesaj', mesaj);
                if(hedefDepartman !== '') data.append('departman', hedefDepartman);
                else data.append('alici_id', aliciId);

                fetch('api_mesaj.php', { method: 'POST', body: data })
                .then(response => response.json())
                .then(data => {
                    if(data.durum === 'basarili') {
                        mesajGirdi.value = ''; 
                        mesajlariGetir(); 
                    }
                });
            });
        }

        mesajlariGetir();
        setInterval(mesajlariGetir, 2000); 
        <?php endif; ?>
    </script>
</body>
</html>