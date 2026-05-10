<?php
class Salle {
    private $id_salle;
    private $numero;
    private $statut;
    private $id_medecin;

    public function __construct($id_salle, $numero, $statut, $id_medecin) {
        $this->id_salle   = $id_salle;
        $this->numero     = intval($numero);
        $this->statut     = $statut;
        $this->id_medecin = $id_medecin;
    }

    // ── Getters ──
    public function getIdSalle()   { return $this->id_salle; }
    public function getNumero()    { return $this->numero; }
    public function getStatut()    { return $this->statut; }
    public function getIdMedecin() { return $this->id_medecin; }

    // ── Setters ──
    public function setIdSalle($v)   { $this->id_salle = $v; }
    public function setNumero($v)    { $this->numero = $v; }
    public function setStatut($v)    { $this->statut = $v; }
    public function setIdMedecin($v) { $this->id_medecin = $v; }
}
?>
