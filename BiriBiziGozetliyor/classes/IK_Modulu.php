<?php
class IK_Modulu {
    private $db;

    public function __construct($db) { $this->db = $db; }

    public function izinTalepEt($personel_id, $baslangic, $bitis, $tur) {
        $sql = "INSERT INTO izinler (personel_id, baslangic_tarihi, bitis_tarihi, izin_turu) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$personel_id, $baslangic, $bitis, $tur]);
    }

    public function izinDurumuGuncelle($izin_id, $yeni_durum) {

        $sql = "UPDATE izinler SET durum = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([$yeni_durum, $izin_id]);
    }
}
?>