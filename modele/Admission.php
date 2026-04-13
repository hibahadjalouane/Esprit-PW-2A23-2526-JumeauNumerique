<?php
class Admission {
    private $id_admission;
    private $date_arrive_relle;
    private $mode_entree;
    private $id_ticket;

    public function __construct($id_admission, $date_arrive_relle, $mode_entree, $id_ticket) {
        $this->id_admission      = $id_admission;
        $this->date_arrive_relle = $date_arrive_relle;
        $this->mode_entree       = $mode_entree;
        $this->id_ticket         = $id_ticket;
    }

    // ── Getters ──
    public function getIdAdmission()     { return $this->id_admission; }
    public function getDateArriveRelle() { return $this->date_arrive_relle; }
    public function getModeEntree()      { return $this->mode_entree; }
    public function getIdTicket()        { return $this->id_ticket; }

    // ── Setters ──
    public function setIdAdmission($v)     { $this->id_admission = $v; }
    public function setDateArriveRelle($v) { $this->date_arrive_relle = $v; }
    public function setModeEntree($v)      { $this->mode_entree = $v; }
    public function setIdTicket($v)        { $this->id_ticket = $v; }
}
?>
