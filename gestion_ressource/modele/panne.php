<?php
class Panne {
    private $id_Panne;
    private $date_de_panne;
    private $date_de_reparation;
    private $statut;
    private $description;
    private $id_ressource;

    public function __construct($id_Panne, $date_de_panne, $date_de_reparation, $statut, $description, $id_ressource) {
        $this->id_Panne           = $id_Panne;
        $this->date_de_panne      = $date_de_panne;
        $this->date_de_reparation = $date_de_reparation;
        $this->statut             = $statut;
        $this->description        = $description;
        $this->id_ressource       = $id_ressource;
    }

    // ── Getters ──
    public function getIdPanne()           { return $this->id_Panne; }
    public function getDateDePanne()       { return $this->date_de_panne; }
    public function getDateDeReparation()  { return $this->date_de_reparation; }
    public function getStatut()            { return $this->statut; }
    public function getDescription()       { return $this->description; }
    public function getIdRessource()       { return $this->id_ressource; }

    // ── Setters ──
    public function setIdPanne($v)             { $this->id_Panne = $v; }
    public function setDateDePanne($v)         { $this->date_de_panne = $v; }
    public function setDateDeReparation($v)    { $this->date_de_reparation = $v; }
    public function setStatut($v)              { $this->statut = $v; }
    public function setDescription($v)         { $this->description = $v; }
    public function setIdRessource($v)         { $this->id_ressource = $v; }
}
?>