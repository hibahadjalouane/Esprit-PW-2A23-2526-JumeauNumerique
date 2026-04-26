<?php
class Ressource {
    private $id_ressource;
    private $Nom;
    private $Type;
    private $Dernier_Maintenence;
    private $Statut;
    private $Localisation;

    public function __construct($id_ressource, $Nom, $Type, $Dernier_Maintenence, $Statut, $Localisation) {
        $this->id_ressource        = $id_ressource;
        $this->Nom                 = $Nom;
        $this->Type                = $Type;
        $this->Dernier_Maintenence = $Dernier_Maintenence;
        $this->Statut              = $Statut;
        $this->Localisation        = $Localisation;
    }

    // ── Getters ──
    public function getIdRessource()        { return $this->id_ressource; }
    public function getNom()                { return $this->Nom; }
    public function getType()               { return $this->Type; }
    public function getDernierMaintenence() { return $this->Dernier_Maintenence; }
    public function getStatut()             { return $this->Statut; }
    public function getLocalisation()       { return $this->Localisation; }

    // ── Setters ──
    public function setIdRessource($v)        { $this->id_ressource = $v; }
    public function setNom($v)                { $this->Nom = $v; }
    public function setType($v)               { $this->Type = $v; }
    public function setDernierMaintenence($v) { $this->Dernier_Maintenence = $v; }
    public function setStatut($v)             { $this->Statut = $v; }
    public function setLocalisation($v)       { $this->Localisation = $v; }
}
?>