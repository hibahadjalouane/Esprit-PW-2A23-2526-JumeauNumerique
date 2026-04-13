<?php

class Consultation
{
    private $conn;
    private $table = "consultation";

    public $id_consultation;
    public $date_consultation;
    public $motif;
    public $diagnostic;
    public $notes;
    public $id_dossier;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ➕ ADD CONSULTATION
    public function add()
    {
        $query = "INSERT INTO " . $this->table . "
        (date_consultation, motif, diagnostic, notes, id_dossier)
        VALUES (:date_consultation, :motif, :diagnostic, :notes, :id_dossier)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':date_consultation', $this->date_consultation);
        $stmt->bindParam(':motif', $this->motif);
        $stmt->bindParam(':diagnostic', $this->diagnostic);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':id_dossier', $this->id_dossier);

        return $stmt->execute();
    }

    // 📄 GET CONSULTATIONS BY DOSSIER
    public function getByDossier($id_dossier)
    {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE id_dossier = :id_dossier
                  ORDER BY date_consultation DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_dossier', $id_dossier);
        $stmt->execute();

        return $stmt;
    }

    // ✏️ UPDATE CONSULTATION
    public function update()
    {
        $query = "UPDATE " . $this->table . "
                  SET date_consultation = :date_consultation,
                      motif = :motif,
                      diagnostic = :diagnostic,
                      notes = :notes
                  WHERE id_consultation = :id_consultation";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_consultation', $this->id_consultation);
        $stmt->bindParam(':date_consultation', $this->date_consultation);
        $stmt->bindParam(':motif', $this->motif);
        $stmt->bindParam(':diagnostic', $this->diagnostic);
        $stmt->bindParam(':notes', $this->notes);

        return $stmt->execute();
    }

    // ❌ DELETE CONSULTATION
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id_consultation = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}