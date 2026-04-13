<?php

require_once __DIR__ . '/../models/Consultation.php';

class ConsultationController
{
    private $consultation;

    public function __construct($conn)
    {
        $this->consultation = new Consultation($conn);
    }

    // ➕ ADD CONSULTATION
    public function add($data)
    {
        $this->consultation->date_consultation = $data['date_consultation'];
        $this->consultation->motif = $data['motif'];
        $this->consultation->diagnostic = $data['diagnostic'];
        $this->consultation->notes = $data['notes'];
        $this->consultation->id_dossier = $data['id_dossier'];

        return $this->consultation->add();
    }

    // 📄 GET CONSULTATIONS BY DOSSIER
    public function getByDossier($id_dossier)
    {
        return $this->consultation->getByDossier($id_dossier);
    }

    // ✏️ UPDATE CONSULTATION
    public function update($data)
    {
        $this->consultation->id_consultation = $data['id_consultation'];
        $this->consultation->date_consultation = $data['date_consultation'];
        $this->consultation->motif = $data['motif'];
        $this->consultation->diagnostic = $data['diagnostic'];
        $this->consultation->notes = $data['notes'];

        return $this->consultation->update();
    }

    // ❌ DELETE CONSULTATION
    public function delete($id)
    {
        return $this->consultation->delete($id);
    }
}