<style>
    :root { 
        --sidebar-bg: #ffffff; 
        --text-main: #1f2937; 
        --text-muted: #6b7280; 
        --primary-color: #4f46e5; 
        --border-color: #e5e7eb; 
        --danger-color: #ef4444;
    }
    @media (prefers-color-scheme: dark) { 
        :root { 
            --sidebar-bg: #1f2937; 
            --text-main: #f9fafb; 
            --text-muted: #9ca3af; 
            --border-color: #374151; 
        } 
    }

    .sidebar { 
        width: 260px; 
        background-color: var(--sidebar-bg); 
        border-right: 1px solid var(--border-color); 
        padding: 20px; 
        display: flex; 
        flex-direction: column; 
        overflow-y: auto;
        height: 100vh; 
        position: sticky; 
        top: 0;
        flex-shrink: 0; 
    }
    
    .sidebar .logo { 
        font-size: 20px; 
        font-weight: 700; 
        color: var(--primary-color); 
        margin-bottom: 20px; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    
    .sidebar .menu-category { 
        font-size: 11px; 
        text-transform: uppercase; 
        color: var(--text-muted); 
        font-weight: 700; 
        margin: 25px 0 10px 15px; 
        letter-spacing: 1px; 
    }
    
    .sidebar .menu-item { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        padding: 12px 15px; 
        color: var(--text-main); 
        text-decoration: none; 
        border-radius: 8px; 
        margin-bottom: 5px; 
        font-weight: 600; 
        font-size: 14px;
        transition: all 0.2s ease;
    }
    
    .sidebar .menu-item:hover, .sidebar .menu-item.active { 
        background-color: var(--primary-color); 
        color: white; 
    }
</style>

<nav class="sidebar" id="sidebar">
    <div class="logo">
        <i class="fa-solid fa-eye"></i> Gözetim Merkezi
    </div>
    
    <div class="menu-category">Ana Ekran</div>
    <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-house"></i> Kontrol Paneli</a>
    <a href="profil.php" class="menu-item"><i class="fa-solid fa-id-card"></i> Hesabım / Profil</a>
    
    <a href="gorevler.php" class="menu-item" style="position: relative;">
        <i class="fa-solid fa-list-check"></i> Görevlerim
        <span id="gorevBadge" style="background: var(--danger-color); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; position: absolute; right: 15px; display: none; box-shadow: 0 0 5px rgba(239,68,68,0.5);"></span>
    </a>
    
    <a href="izinler.php" class="menu-item"><i class="fa-solid fa-calendar-check"></i> İzin İşlemleri</a>
    
    <a href="mesajlar.php" class="menu-item" style="position: relative;">
        <i class="fa-solid fa-comments"></i> İç İletişim
        <span id="mesajBadge" style="background: var(--danger-color); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; position: absolute; right: 15px; display: none; box-shadow: 0 0 5px rgba(239,68,68,0.5);"></span>
    </a>

    <?php if(isset($_SESSION['rol']) && ($_SESSION['rol'] == 'yonetici' || $_SESSION['rol'] == 'ik')): ?>
        <div class="menu-category">İnsan Kaynakları (İK)</div>
        <a href="personeller.php" class="menu-item"><i class="fa-solid fa-users"></i> Personeller</a>
        <a href="performans.php" class="menu-item"><i class="fa-solid fa-chart-line"></i> Performans Değerlendirme</a>
    <?php endif; ?>

    <?php if(isset($_SESSION['rol']) && ($_SESSION['rol'] == 'yonetici' || $_SESSION['rol'] == 'satis')): ?>
        <div class="menu-category">CRM (Müşteri Yönetimi)</div>
        <a href="musteriler.php" class="menu-item"><i class="fa-solid fa-building"></i> Firmalar / Müşteriler</a>
        <a href="satis_firsatlari.php" class="menu-item"><i class="fa-solid fa-handshake"></i> Satış Fırsatları</a>
        <a href="teklifler.php" class="menu-item"><i class="fa-solid fa-file-invoice-dollar"></i> Teklifler & Sözleşmeler</a>
        <a href="destek.php" class="menu-item"><i class="fa-solid fa-headset"></i> Destek Talepleri</a>
    <?php endif; ?>

    <?php if(isset($_SESSION['rol']) && ($_SESSION['rol'] == 'yonetici' || $_SESSION['rol'] == 'it')): ?>
        <div class="menu-category">Sistem</div>
        <a href="loglar.php" class="menu-item"><i class="fa-solid fa-file-shield"></i> Gözetleme Logları</a>
        <a href="departmanlar.php" class="menu-item"><i class="fa-solid fa-sitemap"></i> Departmanlar</a>
        <a href="ayarlar.php" class="menu-item"><i class="fa-solid fa-gear"></i> Ayarlar</a>
    <?php endif; ?>

    <a href="logout.php" class="menu-item" style="margin-top: auto; color: var(--danger-color); border: 1px solid rgba(239,68,68,0.2); transition: all 0.3s;">
        <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
    </a>
</nav>

<script>
    function okunmayanMesajlariSay() {
        fetch('api_mesaj_say.php')
        .then(response => response.json())
        .then(data => {
             let count = parseInt(data.toplam);
            let badge = document.getElementById('mesajBadge');
            if (badge) {
                if (count > 0) {
                    badge.style.display = 'inline-block';
                    badge.innerText = count > 5 ? '5+' : count;
                } else {
                    badge.style.display = 'none';
                }
            }
            
                        let gCount = parseInt(data.gorevler);
            let gBadge = document.getElementById('gorevBadge');
            if (gBadge) {
                if (gCount > 0) {
                    gBadge.style.display = 'inline-block';
                    gBadge.innerText = gCount; 
                } else {
                    gBadge.style.display = 'none';
                }
            }
            
            if (typeof guncelleListeRozetleri === 'function') {
                guncelleListeRozetleri(data.kisiler, data.departmanlar);
            }
        })
        .catch(error => console.error('Bildirim çekilemedi:', error));
    }

    okunmayanMesajlariSay();
    setInterval(okunmayanMesajlariSay, 3000); 
</script>