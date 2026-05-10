<?php
class Creneau
{
    private ?int    $idCreneau   = null;
    private ?string $dateCreneau = null;
    private ?string $heureDebut  = null;
    private ?string $heureFin    = null;
    private ?string $statut      = null;
    private ?int    $idMedecin   = null;

    public function __construct($id = null, $dc, $hd, $hf, $s, $im)
    {
        $this->idCreneau   = $id  !== null ? (int)$id  : null;
        $this->dateCreneau = $dc;
        $this->heureDebut  = $hd;
        $this->heureFin    = $hf;
        $this->statut      = $s;
        $this->idMedecin   = $im !== null ? (int)$im : null;
    }

    public function getIdCreneau(): ?int   { return $this->idCreneau; }

    public function getDateCreneau(): ?string { return $this->dateCreneau; }
    public function setDateCreneau($dc) { $this->dateCreneau = $dc; return $this; }

    public function getHeureDebut(): ?string { return $this->heureDebut; }
    public function setHeureDebut($hd) { $this->heureDebut = $hd; return $this; }

    public function getHeureFin(): ?string { return $this->heureFin; }
    public function setHeureFin($hf) { $this->heureFin = $hf; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut($s) { $this->statut = $s; return $this; }

    public function getIdMedecin(): ?int { return $this->idMedecin; }
    public function setIdMedecin($im) { $this->idMedecin = $im !== null ? (int)$im : null; return $this; }
}
?>