<?php
class Facture {
    private $id_facture;
    private $montant;
    private $statut;
    private $date_facture;
    private $id_rdv;
    private $id_type_paiement;
    private $id_ligneOrd;
    private $id_patient;
    private $id_ressource_assignee;

    public function __construct(
        $id_facture, $montant, $statut, $date_facture,
        $id_rdv, $id_type_paiement, $id_ligneOrd,
        $id_patient, $id_ressource_assignee
    ) {
        $this->id_facture           = $id_facture;
        $this->montant              = $montant;
        $this->statut               = $statut;
        $this->date_facture         = $date_facture;
        $this->id_rdv               = $id_rdv;
        $this->id_type_paiement     = $id_type_paiement;
        $this->id_ligneOrd          = $id_ligneOrd;
        $this->id_patient           = $id_patient;
        $this->id_ressource_assignee = $id_ressource_assignee;
    }

    public function getIdFacture()           { return $this->id_facture; }
    public function getMontant()             { return $this->montant; }
    public function getStatut()              { return $this->statut; }
    public function getDateFacture()         { return $this->date_facture; }
    public function getIdRdv()               { return $this->id_rdv; }
    public function getIdTypePaiement()      { return $this->id_type_paiement; }
    public function getIdLigneOrd()          { return $this->id_ligneOrd; }
    public function getIdPatient()           { return $this->id_patient; }
    public function getIdRessourceAssignee() { return $this->id_ressource_assignee; }

    public function setIdFacture($v)           { $this->id_facture = $v; }
    public function setMontant($v)             { $this->montant = $v; }
    public function setStatut($v)              { $this->statut = $v; }
    public function setDateFacture($v)         { $this->date_facture = $v; }
    public function setIdRdv($v)               { $this->id_rdv = $v; }
    public function setIdTypePaiement($v)      { $this->id_type_paiement = $v; }
    public function setIdLigneOrd($v)          { $this->id_ligneOrd = $v; }
    public function setIdPatient($v)           { $this->id_patient = $v; }
    public function setIdRessourceAssignee($v) { $this->id_ressource_assignee = $v; }
}
?>
