<?php
session_start();
require_once 'config/db.php';
require_once 'classes/Auth.php';

$auth = new Auth($db);
$auth->yetkiKontrol(['yonetici', 'it']); 

$sql = "SELECT l.*, p.ad_soyad 
        FROM loglar l 
        LEFT JOIN personeller p ON l.personel_id = p.id 
        ORDER BY l.id DESC";
$loglar = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

    <h2><i class="fa-solid fa-user-secret" style="color: var(--primary-color);"></i> Sistem Logları (Gözetim Merkezi)</h2>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Sistemdeki tüm hareketleri, veri girişlerini ve silme işlemlerini saniye saniye takip edin.</p>

    <div class="card" style="margin-bottom: 25px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(79, 70, 229, 0.02) 100%);">
        <div style="display: flex; gap: 15px; align-items: center;">
            <i class="fa-solid fa-shield-halved" style="font-size: 32px; color: var(--primary-color);"></i>
            <div>
                <h3 style="margin: 0 0 5px 0;">Siber Güvenlik ve Denetim Kayıtları</h3>
                <p style="margin: 0; font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Bu sayfa, şirket içi güvenlik politikaları gereği tüm kullanıcı hareketlerini kayıt altına alır. Kayıtlar silinemez ve değiştirilemez. <strong>Sağ üstteki butonu kullanarak geçmiş denetim loglarını Excel dosyası (.xlsx) olarak bilgisayarınıza indirebilirsiniz.</strong>
                </p>
            </div>
        </div>
    </div>

    <div class="card">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> İşlem Geçmişi Dökümü</h3>
        <div style="overflow-x: auto; margin-top: 15px;">
            <table id="logTable" class="display" style="width:100%; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th style="background: rgba(0,0,0,0.02); padding: 15px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Tarih / Saat</th>
                        <th style="background: rgba(0,0,0,0.02); padding: 15px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--border-color);">İşlemi Yapan Personel</th>
                        <th style="background: rgba(0,0,0,0.02); padding: 15px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--border-color);">İşlem Türü (Modül)</th>
                        <th style="background: rgba(0,0,0,0.02); padding: 15px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--border-color);">Detay / Açıklama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($loglar as $l): 
                        // İşlem türündeki kelimelere göre akıllı rozet renklendirmesi
                        $tur = strtolower($l['islem_turu']);
                        $renk = 'bg-bekliyor'; // Varsayılan Turuncu
                        
                        if(strpos($tur, 'ekle') !== false || strpos($tur, 'onay') !== false) $renk = 'bg-kabul'; // Yeşil
                        if(strpos($tur, 'sil') !== false || strpos($tur, 'red') !== false) $renk = 'bg-red'; // Kırmızı
                        if(strpos($tur, 'guncelle') !== false || strpos($tur, 'duzenle') !== false) $renk = 'bg-islemde'; // Mavi-Turuncu
                    ?>
                    <tr>
                        <td data-sort="<?php echo $l['tarih']; ?>" style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                            <strong><?php echo date('d.m.Y', strtotime($l['tarih'])); ?></strong><br>
                            <span style="font-size:11px; color:var(--text-muted);"><i class="fa-regular fa-clock"></i> <?php echo date('H:i:s', strtotime($l['tarih'])); ?></span>
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                            <?php if($l['ad_soyad']): ?>
                                <strong><i class="fa-solid fa-user-tie" style="font-size:11px; color:var(--primary-color);"></i> <?php echo htmlspecialchars($l['ad_soyad']); ?></strong>
                            <?php else: ?>
                                <strong style="color:var(--text-muted);"><i class="fa-solid fa-robot"></i> Sistem (Otomatik)</strong>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                            <span class="badge <?php echo $renk; ?>"><?php echo mb_strtoupper($l['islem_turu']); ?></span>
                        </td>
                        <td style="font-size: 13px; padding: 15px; border-bottom: 1px solid var(--border-color);">
                            <?php echo htmlspecialchars($l['detay']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Standart DataTables ayarını Excel butonlarıyla eziyoruz
            $('#logTable').DataTable({
                "order": [[ 0, "desc" ]], // En yeni log her zaman en üstte
                "pageLength": 25, // Tek sayfada 25 kayıt göster
                "dom": '<"top"Bf>rt<"bottom"ip><"clear">', // Excel butonunu Arama kutusunun yanına koyar
                "buttons": [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa-solid fa-file-excel"></i> Excel Çıktısı Al',
                        className: 'btn', // Bizim CSS'deki premium buton tasarımını kullanır
                        title: 'Sistem_Denetim_Loglari_' + new Date().toISOString().slice(0,10) // Dosya adına bugünün tarihini atar
                    }
                ],
                // Türkçe dil ayarları
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json"
                }
            });
            

            setTimeout(() => {
                $('.dt-buttons .dt-button').removeClass('dt-button').css({
                    'background': '#10b981', 'color': 'white', 'border': 'none', 'padding': '8px 15px', 
                    'border-radius': '6px', 'font-weight': '600', 'cursor': 'pointer', 'margin-right': '15px'
                });
            }, 100);
        });
    </script>

<?php include 'footer.php'; ?>
</div> <?php 
include 'footer.php'; 
?>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#logTable').DataTable({
            "order": [[ 0, "desc" ]], 
            "pageLength": 25, 
            "dom": '<"top"Bf>rt<"bottom"ip><"clear">', 
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel"></i> Excel Çıktısı Al',
                    className: 'btn', 
                    title: 'Sistem_Denetim_Loglari_' + new Date().toISOString().slice(0,10) 
                }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json"
            }
        });
        
        setTimeout(() => {
            $('.dt-buttons .dt-button').removeClass('dt-button').css({
                'background': '#10b981', 'color': 'white', 'border': 'none', 'padding': '8px 15px', 
                'border-radius': '6px', 'font-weight': '600', 'cursor': 'pointer', 'margin-right': '15px'
            });
        }, 100);
    });
</script>