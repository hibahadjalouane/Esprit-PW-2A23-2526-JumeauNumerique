<?php
class Dossier {
    private $id_dossier;
    private $description;
    private $date_creation;
    private $id_patient;
    private $id_medecin;

    public function __construct($id_dossier, $description, $date_creation, $id_patient, $id_medecin) {
        $this->id_dossier    = $id_dossier;
        $this->description   = $description;
        $this->date_creation = $date_creation;
        $this->id_patient    = $id_patient;
        $this->id_medecin    = $id_medecin;
    }

    // ── Getters ──
    public function getIdDossier()    { return $this->id_dossier; }
    public function getDescription()  { return $this->description; }
    public function getDateCreation() { return $this->date_creation; }
    public function getIdPatient()    { return $this->id_patient; }
    public function getIdMedecin()    { return $this->id_medecin; }

    // ── Setters ──
    public function setIdDossier($v)    { $this->id_dossier = $v; }
    public function setDescription($v)  { $this->description = $v; }
    public function setDateCreation($v) { $this->date_creation = $v; }
    public function setIdPatient($v)    { $this->id_patient = $v; }
    public function setIdMedecin($v)    { $this->id_medecin = $v; }
}
?>
