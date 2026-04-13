<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DossierMedical.php';

class DossierMedicalController
{
    private $db;
    private $dossier;

    public function __construct($conn)
    {
        $this->db = $conn;
        $this->dossier = new DossierMedical($conn);
    }

    // ➕ ADD DOSSIER
    public function add($data)
    {
        $this->dossier->id_dossier = $data['id_dossier'];
        $this->dossier->description = $data['description'];
        $this->dossier->date_creation = $data['date_creation'];
        $this->dossier->id_patient = $data['id_patient'];
        $this->dossier->id_medecin = $data['id_medecin'];

        return $this->dossier->add();
    }

    // 📄 GET ALL
    public function getAll()
    {
        return $this->dossier->getAll();
    }

    // 🔍 GET ONE
    public function getOne($id)
    {
        $this->dossier->id_dossier = $id;
        return $this->dossier->getOne();
    }

    // ✏️ UPDATE
    public function update($data)
    {
        $this->dossier->id_dossier = $data['id_dossier'];
        $this->dossier->description = $data['description'];
        $this->dossier->date_creation = $data['date_creation'];
        $this->dossier->id_patient = $data['id_patient'];
        $this->dossier->id_medecin = $data['id_medecin'];

        return $this->dossier->update();
    }

    // ❌ DELETE
    public function delete($id)
    {
        $this->dossier->id_dossier = $id;
        return $this->dossier->delete();
    }
}