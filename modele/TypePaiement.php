<?php
class TypePaiement {
    private $id_type;
    private $nom_type;
    private $description;
    private $montant;

    public function __construct($id_type, $nom_type, $description, $montant) {
        $this->id_type     = $id_type;
        $this->nom_type    = $nom_type;
        $this->description = $description;
        $this->montant     = $montant;
    }

    // ── Getters ──
    public function getIdType()      { return $this->id_type; }
    public function getNomType()     { return $this->nom_type; }
    public function getDescription() { return $this->description; }
    public function getMontant()     { return $this->montant; }

    // ── Setters ──
    public function setIdType($v)      { $this->id_type = $v; }
    public function setNomType($v)     { $this->nom_type = $v; }
    public function setDescription($v) { $this->description = $v; }
    public function setMontant($v)     { $this->montant = $v; }
}
?>
