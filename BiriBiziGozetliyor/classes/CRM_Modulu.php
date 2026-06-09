<?php
class CRM_Modulu {
    private $db;

    public function __construct($db) { $this->db = $db; }

    public function firsatEkle($musteri_id, $personel_id, $firsat_adi, $tutar) {
        $sql = "INSERT INTO satis_firsatlari (musteri_id, ilgilenen_personel_id, firsat_adi, tahmini_tutar) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$musteri_id, $personel_id, $firsat_adi, $tutar]);
    }

    public function gorevAta($personel_id, $baslik, $son_tarih, $musteri_id = null) {
        $sql = "INSERT INTO gorevler (atanan_personel_id, ilgili_musteri_id, baslik, son_tarih) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$personel_id, $musteri_id, $baslik, $son_tarih]);
    }
}
?>