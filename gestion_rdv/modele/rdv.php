<?php
class Rdv
{
    private ?int    $idRdv            = null;
    private ?string $dateDemande      = null;
    private ?string $dateRdv          = null;
    private ?string $statut           = null;
    private ?string $typeConsultation = null;
    private ?int    $idPatient        = null;
    private ?int    $idCreneau        = null;
    private ?int    $idMedecin        = null;

    public function __construct($id = null, $dd, $dr, $s, $tc, $ip, $ic, $im)
    {
        $this->idRdv            = $id !== null ? (int)$id : null;
        $this->dateDemande      = $dd;
        $this->dateRdv          = $dr;
        $this->statut           = $s;
        $this->typeConsultation = $tc;
        $this->idPatient        = $ip !== null ? (int)$ip : null;
        $this->idCreneau        = $ic !== null ? (int)$ic : null;
        $this->idMedecin        = $im !== null ? (int)$im : null;
    }

    public function getIdRdv(): ?int            { return $this->idRdv; }

    public function getDateDemande(): ?string    { return $this->dateDemande; }
    public function setDateDemande($dd)         { $this->dateDemande = $dd; return $this; }

    public function getDateRdv(): ?string        { return $this->dateRdv; }
    public function setDateRdv($dr)             { $this->dateRdv = $dr; return $this; }

    public function getStatut(): ?string         { return $this->statut; }
    public function setStatut($s)               { $this->statut = $s; return $this; }

    public function getTypeConsultation(): ?string { return $this->typeConsultation; }
    public function setTypeConsultation($tc)      { $this->typeConsultation = $tc; return $this; }

    public function getIdPatient(): ?int  { return $this->idPatient; }
    public function setIdPatient($ip)    { $this->idPatient = $ip !== null ? (int)$ip : null; return $this; }

    public function getIdCreneau(): ?int  { return $this->idCreneau; }
    public function setIdCreneau($ic)    { $this->idCreneau = $ic !== null ? (int)$ic : null; return $this; }

    public function getIdMedecin(): ?int  { return $this->idMedecin; }
    public function setIdMedecin($im)    { $this->idMedecin = $im !== null ? (int)$im : null; return $this; }
}
?>