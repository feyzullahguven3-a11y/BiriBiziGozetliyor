<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';
require_once 'classes/Gozetmen.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'ik']); 

$gozetmen = new Gozetmen($db);
$mesaj = "";

$sistem_ayari = $db->query("SELECT email_domain FROM ayarlar WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$domain = $sistem_ayari['email_domain'] ?? 'sirket.com';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hizli_sifre_degistir'])) {
    if ($_SESSION['rol'] == 'yonetici' || $_SESSION['rol'] == 'it') {
        $p_id = $_POST['personel_id'];
        $yeni_sifre_hash = password_hash($_POST['yeni_sifre'], PASSWORD_DEFAULT);
        $db->prepare("UPDATE personeller SET sifre_hash = ? WHERE id = ?")->execute([$yeni_sifre_hash, $p_id]);
        $gozetmen->logTut($_SESSION['personel_id'], 'sifre_sifirlama', "ID: $p_id personelin şifresini sıfırladı.");
        exit; 
    }
}

if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    
    // Kendi kendini silmesini engelle
    if ($sil_id == $_SESSION['personel_id']) {
        $mesaj = "<script>Swal.fire({ title: 'Hata!', text: 'Kendi hesabınızı silemezsiniz!', icon: 'error' });</script>";
    } else {
        try {
            $db->prepare("DELETE FROM personeller WHERE id = ?")->execute([$sil_id]);
            $gozetmen->logTut($_SESSION['personel_id'], 'personel_silindi', "ID: $sil_id olan personeli sildi.");
            $mesaj = "<script>Swal.fire({ title: 'Silindi!', text: 'Personel sistemden kalıcı olarak kaldırıldı.', icon: 'success' }).then(() => { window.location = 'personeller.php'; });</script>";
        } catch (PDOException $e) {
            $mesaj = "<script>Swal.fire({ title: 'Silinemedi!', text: 'Bu personelin sistemde kayıtlı işlem geçmişi veya görevleri olabilir.', icon: 'warning' });</script>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['personel_guncelle'])) {
    try {
        $id = (int)$_POST['p_id'];
        $ad_soyad = trim($_POST['ad_soyad']);
        $email = trim($_POST['email']);
        $telefon = trim($_POST['telefon']);
        $tc_kimlik = trim($_POST['tc_kimlik']);
        $adres = trim($_POST['adres']);
        $departman = $_POST['departman'];
        $rol = $_POST['rol'];
        $durum = $_POST['durum'];
        $kalan_izin = (int)$_POST['kalan_izin'];

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "UPDATE personeller SET ad_soyad=?, email=?, telefon=?, tc_kimlik=?, adres=?, departman=?, rol=?, durum=?, kalan_yillik_izin=? WHERE id=?";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([$ad_soyad, $email, $telefon, $tc_kimlik, $adres, $departman, $rol, $durum, $kalan_izin, $id])) {
            $gozetmen->logTut($_SESSION['personel_id'], 'personel_guncelledi', "$ad_soyad profilini güncelledi.");
            $mesaj = "<script>Swal.fire({ title: 'Güncellendi!', text: 'Personel bilgileri başarıyla kaydedildi.', icon: 'success' }).then(()=>{window.location='personeller.php'});</script>";
        }
    } catch (PDOException $e) {
        $hata_mesaji = addslashes($e->getMessage());
        $mesaj = "<script>Swal.fire({ title: 'Veritabanı Hatası!', html: '<b>Hata Nedeni:</b><br>" . $hata_mesaji . "', icon: 'error' });</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['personel_ekle'])) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['mail_kullanici']) . "@" . $domain; 
    $sifre_hash = password_hash(trim($_POST['sifre']), PASSWORD_DEFAULT);
    $departman = $_POST['departman'];
    $rol = $_POST['rol'];

    $foto_adi = 'default.png';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $uzanti = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_adi = "kullanici_" . time() . "." . $uzanti;
        move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_adi);
    }

    try {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

         $sql = "INSERT INTO personeller (ad_soyad, email, departman, sifre_hash, rol, durum, foto) VALUES (?, ?, ?, ?, ?, 'aktif', ?)";
        $db->prepare($sql)->execute([$ad_soyad, $email, $departman, $sifre_hash, $rol, $foto_adi]);
        
        $gozetmen->logTut($_SESSION['personel_id'], 'personel_eklendi', "$ad_soyad isimli yeni personel eklendi.");

        $it_sorgu = $db->query("SELECT id FROM personeller WHERE rol = 'it' AND durum = 'aktif'");
        $hedef_personeller = $it_sorgu->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hedef_personeller)) {
            $yonetici_sorgu = $db->query("SELECT id FROM personeller WHERE rol = 'yonetici' AND durum = 'aktif'");
            $hedef_personeller = $yonetici_sorgu->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!empty($hedef_personeller)) {
            $gorev_baslik = "Yeni Kurumsal E-Posta Açılışı";
            $gorev_aciklama = "Sisteme yeni kaydedilen <b>$ad_soyad</b> isimli çalışan için sunucuda <b>$email</b> adresli kurumsal mail hesabının acilen oluşturulması gerekmektedir.";
            $bitis_tarihi = date('Y-m-d', strtotime('+2 days'));
            
            $gorev_sql = "INSERT INTO gorevler (baslik, aciklama, atanan_id, bitis_tarihi, durum) VALUES (?, ?, ?, ?, 'yapilacak')";
            $stmt_gorev = $db->prepare($gorev_sql);
            
            foreach ($hedef_personeller as $hedef) {
                $stmt_gorev->execute([$gorev_baslik, $gorev_aciklama, $hedef['id'], $bitis_tarihi]);
            }
            
            $mesaj = "<script>Swal.fire({ title: 'İşlem Başarılı!', text: 'Yeni personel eklendi ve mail açılış görevi otomatik atandı.', icon: 'success' });</script>";
        } else {
            $mesaj = "<script>Swal.fire({ title: 'Görev Atanamadı!', text: 'Personel eklendi ancak sistemde aktif IT veya Yönetici bulunamadığı için mail açılış görevi atanamadı.', icon: 'warning' });</script>";
        }

    } catch(PDOException $e) {
        $hata_detay = addslashes($e->getMessage());
        $mesaj = "<script>Swal.fire({ title: 'SQL Hatası!', html: '<small style=\"color:red\">" . $hata_detay . "</small>', icon: 'error' });</script>";
    }
}

$duzenlenecek = null;
if (isset($_GET['duzenle'])) {
    $d_id = (int)$_GET['duzenle'];
    $duzenlenecek = $db->query("SELECT * FROM personeller WHERE id=$d_id")->fetch(PDO::FETCH_ASSOC);
}

$departmanlar = $db->query("SELECT ad FROM departmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
$personeller_liste = $db->query("SELECT * FROM personeller ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-users" style="color: var(--primary-color);"></i> Personel Yönetimi</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Personelleri ekleyin, düzenleyin ve sistem yetkilerini ayarlayın.</p>

    <?php echo $mesaj; ?>

    <div class="card" style="<?php echo $duzenlenecek ? 'border: 2px solid var(--primary-color);' : ''; ?>">
        <h3>
            <?php if($duzenlenecek): ?>
                <i class="fa-solid fa-pen-to-square" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($duzenlenecek['ad_soyad']); ?> Bilgilerini Düzenle
                <a href="personeller.php" class="btn btn-sm" style="float: right; background: var(--text-muted);">İptal Et / Yeni Ekle</a>
            <?php else: ?>
                <i class='fa-solid fa-user-plus'></i> Yeni Çalışan Ekle
            <?php endif; ?>
        </h3>
        
        <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
            <?php if($duzenlenecek): ?>
                <input type="hidden" name="p_id" value="<?php echo $duzenlenecek['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" name="ad_soyad" required value="<?php echo $duzenlenecek ? htmlspecialchars($duzenlenecek['ad_soyad']) : ''; ?>">
                </div>
                
                <div class="form-group" style="flex: 1.5;">
                    <label>E-posta Adresi</label>
                    <?php if($duzenlenecek): ?>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($duzenlenecek['email']); ?>">
                    <?php else: ?>
                        <div style="display: flex; align-items: center;">
                            <input type="text" name="mail_kullanici" required style="border-radius: 8px 0 0 8px;">
                            <span style="background: var(--border-color); padding: 12px 15px; border-radius: 0 8px 8px 0; font-weight: bold;">@<?php echo $domain; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if(!$duzenlenecek): ?>
                <div class="form-group">
                    <label>Sistem Şifresi</label>
                    <div style="display: flex; gap: 5px;">
                        <div style="position: relative; flex: 1;">
                            <input type="password" name="sifre" id="sifreInput" required style="width: 100%; padding-right: 40px; box-sizing: border-box;">
                            <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted);" onclick="sifreGosterGizle()" title="Şifreyi Göster/Gizle"></i>
                        </div>
                        <button type="button" class="btn btn-success" style="padding: 0 15px;" title="Otomatik Şifre Üret" onclick="otomatikSifreUret()"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                    </div>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>TC Kimlik No</label>
                    <input type="text" name="tc_kimlik" value="<?php echo htmlspecialchars($duzenlenecek['tc_kimlik'] ?? ''); ?>" maxlength="11">
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" value="<?php echo $duzenlenecek ? htmlspecialchars($duzenlenecek['telefon'] ?? '') : ''; ?>" placeholder="0555...">
                </div>
                <div class="form-group">
                    <label>Departman</label>
                    <select name="departman" required>
                        <option value="">-- Seçin --</option>
                        <?php foreach($departmanlar as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['ad']); ?>" <?php echo ($duzenlenecek && $duzenlenecek['departman'] == $d['ad']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['ad']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sistem Yetkisi (Rol)</label>
                    <select name="rol" required>
                        <option value="personel" <?php echo ($duzenlenecek && $duzenlenecek['rol']=='personel')?'selected':''; ?>>Standart Personel</option>
                        <option value="satis" <?php echo ($duzenlenecek && $duzenlenecek['rol']=='satis')?'selected':''; ?>>Satış Temsilcisi</option>
                        <option value="ik" <?php echo ($duzenlenecek && $duzenlenecek['rol']=='ik')?'selected':''; ?>>İnsan Kaynakları</option>
                        <option value="it" <?php echo ($duzenlenecek && $duzenlenecek['rol']=='it')?'selected':''; ?>>IT / Bilgi İşlem</option>
                        <option value="yonetici" <?php echo ($duzenlenecek && $duzenlenecek['rol']=='yonetici')?'selected':''; ?>>Yönetici</option>
                    </select>
                </div>
            </div>

            <?php if($duzenlenecek): ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Açık Adres</label>
                    <input type="text" name="adres" value="<?php echo htmlspecialchars($duzenlenecek['adres'] ?? ''); ?>">
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>Kalan Yıllık İzin (Gün)</label>
                    <input type="number" name="kalan_izin" value="<?php echo (int)$duzenlenecek['kalan_yillik_izin']; ?>" style="font-weight:bold; color:var(--primary-color);">
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>Hesap Durumu</label>
                    <select name="durum" required>
                        <option value="aktif" <?php echo ($duzenlenecek['durum']=='aktif')?'selected':''; ?>>Aktif</option>
                        <option value="pasif" <?php echo ($duzenlenecek['durum']=='pasif')?'selected':''; ?>>Pasif (Giremez)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="personel_guncelle" class="btn" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet</button>
            <?php else: ?>
            <div class="form-group">
                <label>Profil Fotoğrafı (İsteğe Bağlı)</label>
                <input type="file" name="foto" accept="image/*" style="padding: 9px;">
            </div>
            <button type="submit" name="personel_ekle" class="btn"><i class="fa-solid fa-user-plus"></i> Çalışanı Kaydet</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h3>Kayıtlı Çalışanlar</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Foto</th>
                        <th>Ad Soyad & İletişim</th>
                        <th>Departman</th>
                        <th>İzin</th>
                        <th>Durum</th>
                        <th style="width: 140px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($personeller_liste as $p): 
                        $durumRenk = ($p['durum'] == 'aktif') ? 'var(--success-color)' : 'var(--danger-color)';
                        $foto_url = !empty($p['foto']) && $p['foto'] != 'default.png' ? 'uploads/'.$p['foto'] : "https://ui-avatars.com/api/?name=".urlencode($p['ad_soyad'])."&background=4f46e5&color=fff";
                    ?>
                    <tr>
                        <td><img src="<?php echo $foto_url; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['ad_soyad']); ?></strong><br>
                            <span style="font-size:11px; color:var(--text-muted);"><?php echo htmlspecialchars($p['email']); ?> <br> <?php echo htmlspecialchars($p['telefon'] ?? ''); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($p['departman']); ?></td>
                        <td><strong><?php echo (int)$p['kalan_yillik_izin']; ?> Gün</strong></td>
                        <td><strong style="color: <?php echo $durumRenk; ?>;"><i class="fa-solid fa-circle" style="font-size:8px;"></i> <?php echo ucfirst($p['durum']); ?></strong></td>
                        <td style="display: flex; gap: 5px;">
                            <a href="?duzenle=<?php echo $p['id']; ?>" class="action-btn" style="background: var(--primary-color); padding: 6px 10px;" title="Düzenle"><i class="fa-solid fa-pen-to-square"></i></a>
                            
                            <?php if($_SESSION['rol'] == 'yonetici' || $_SESSION['rol'] == 'it'): ?>
                                <a href="#" onclick="event.preventDefault(); hizliSifreDegistir(<?php echo $p['id']; ?>, '<?php echo addslashes($p['ad_soyad']); ?>');" class="action-btn" title="Şifresini Yenile" style="background: #f59e0b; padding: 6px 10px;"><i class="fa-solid fa-key"></i></a>
                            <?php endif; ?>
                            
                            <a href="#" onclick="event.preventDefault(); Swal.fire({title: 'Emin misiniz?', text: 'Bu personel silinecek!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Evet, Sil!', cancelButtonText: 'İptal'}).then((result) => { if (result.isConfirmed) { window.location.href = '?sil=<?php echo $p['id']; ?>'; } })" class="action-btn btn-danger"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function sifreGosterGizle() {
            let inp = document.getElementById('sifreInput');
            let icon = document.getElementById('togglePassword');
            if (inp.type === "password") {
                inp.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                inp.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function otomatikSifreUret() {
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$*";
            let pwd = "";
            for (let i = 0; i < 10; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            let inp = document.getElementById('sifreInput');
            inp.value = pwd;
            
            inp.type = 'text';
            let icon = document.getElementById('togglePassword');
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }

        function hizliSifreDegistir(personel_id, personel_ad) {
            Swal.fire({
                title: personel_ad + ' İçin Yeni Şifre', input: 'text', inputPlaceholder: 'Yeni şifreyi buraya yazın...',
                showCancelButton: true, confirmButtonColor: '#10b981', confirmButtonText: '<i class="fa-solid fa-save"></i> Şifreyi Kaydet', cancelButtonText: 'İptal',
                inputValidator: (value) => { if (!value) { return 'Şifre alanı boş bırakılamaz!' } }
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = new FormData();
                    formData.append('hizli_sifre_degistir', '1'); formData.append('personel_id', personel_id); formData.append('yeni_sifre', result.value);
                    fetch('personeller.php', { method: 'POST', body: formData }).then(response => response.text()).then(data => {
                        Swal.fire('Başarılı!', personel_ad + ' şifresi değiştirildi. Yeni Şifre: <strong>' + result.value + '</strong>', 'success');
                    });
                }
            });
        }
    </script>

<?php include 'footer.php'; ?>