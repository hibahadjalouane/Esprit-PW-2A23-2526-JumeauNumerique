<?php
class Creneau
{
    private ?string $idCreneau = null;
    private ?string $dateCreneau = null;
    private ?string $heureDebut = null;
    private ?string $heureFin = null;
    private ?string $statut = null;
    private ?string $idMedecin = null;

    public function __construct($id = null, $dc, $hd, $hf, $s, $im)
    {
        $this->idCreneau   = $id;
        $this->dateCreneau = $dc;
        $this->heureDebut  = $hd;
        $this->heureFin    = $hf;
        $this->statut      = $s;
        $this->idMedecin   = $im;
    }

    public function getIdCreneau()
    {
        return $this->idCreneau;
    }

    public function getDateCreneau()
    {
        return $this->dateCreneau;
    }
    public function setDateCreneau($dateCreneau)
    {
        $this->dateCreneau = $dateCreneau;
        return $this;
    }

    public function getHeureDebut()
    {
        return $this->heureDebut;
    }
    public function setHeureDebut($heureDebut)
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin()
    {
        return $this->heureFin;
    }
    public function setHeureFin($heureFin)
    {
        $this->heureFin = $heureFin;
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