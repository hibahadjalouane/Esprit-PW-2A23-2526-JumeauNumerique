<?php
class Consultation {
    private $id_consultation;
    private $date_consultation;
    private $motif;
    private $diagnostic;
    private $notes;
    private $id_dossier;

    public function __construct($id_consultation, $date_consultation, $motif, $diagnostic, $notes, $id_dossier) {
        $this->id_consultation   = $id_consultation;
        $this->date_consultation = $date_consultation;
        $this->motif             = $motif;
        $this->diagnostic        = $diagnostic;
        $this->notes             = $notes;
        $this->id_dossier        = $id_dossier;
    }

    // ── Getters ──
    public function getIdConsultation()   { return $this->id_consultation; }
    public function getDateConsultation() { return $this->date_consultation; }
    public function getMotif()            { return $this->motif; }
    public function getDiagnostic()       { return $this->diagnostic; }
    public function getNotes()            { return $this->notes; }
    public function getIdDossier()        { return $this->id_dossier; }

    // ── Setters ──
    public function setIdConsultation($v)   { $this->id_consultation = $v; }
    public function setDateConsultation($v) { $this->date_consultation = $v; }
    public function setMotif($v)            { $this->motif = $v; }
    public function setDiagnostic($v)       { $this->diagnostic = $v; }
    public function setNotes($v)            { $this->notes = $v; }
    public function setIdDossier($v)        { $this->id_dossier = $v; }
}
?>
