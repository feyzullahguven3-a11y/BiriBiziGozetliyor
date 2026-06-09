<?php

class Gozetmen {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function logTut($personel_id, $islem_turu, $detay) {
        try {
            $sql = "INSERT INTO loglar (personel_id, islem_turu, detay, tarih) VALUES (?, ?, ?, NOW())";
            $this->db->prepare($sql)->execute([$personel_id, $islem_turu, $detay]);
            return true;
        } catch (Exception $e) {
            return false; 
        }
    }
}
?>