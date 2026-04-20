<?php
class Rdv
{
    private ?string $idRdv = null;
    private ?string $dateDemande = null;
    private ?string $dateRdv = null;
    private ?string $statut = null;
    private ?string $typeConsultation = null;
    private ?string $idPatient = null;
    private ?string $idCreneau = null;
    private ?string $idMedecin = null;

    public function __construct($id = null, $dd, $dr, $s, $tc, $ip, $ic, $im)
    {
        $this->idRdv            = $id;
        $this->dateDemande      = $dd;
        $this->dateRdv          = $dr;
        $this->statut           = $s;
        $this->typeConsultation = $tc;
        $this->idPatient        = $ip;
        $this->idCreneau        = $ic;
        $this->idMedecin        = $im;
    }

    public function getIdRdv()
    {
        return $this->idRdv;
    }

    public function getDateDemande()
    {
        return $this->dateDemande;
    }
    public function setDateDemande($dateDemande)
    {
        $this->dateDemande = $dateDemande;
        return $this;
    }

    public function getDateRdv()
    {
        return $this->dateRdv;
    }
    public function setDateRdv($dateRdv)
    {
        $this->dateRdv = $dateRdv;
        return $this;
    }

    public function getStatut()
    {
        return $this->statut;
    }
    public function setStatut($statut)
    {
        $this->statut = $statut;
        return $this;
    }

    public function getTypeConsultation()
    {
        return $this->typeConsultation;
    }
    public function setTypeConsultation($typeConsultation)
    {
        $this->typeConsultation = $typeConsultation;
        return $this;
    }

    public function getIdPatient()
    {
        return $this->idPatient;
    }
    public function setIdPatient($idPatient)
    {
        $this->idPatient = $idPatient;
        return $this;
    }

    public function getIdCreneau()
    {
        return $this->idCreneau;
    }
    public function setIdCreneau($idCreneau)
    {
        $this->idCreneau = $idCreneau;
        return $this;
    }

    public function getIdMedecin()
    {
        return $this->idMedecin;
    }
    public function setIdMedecin($idMedecin)
    {
        $this->idMedecin = $idMedecin;
        return $this;
    }
}
?>