<?php

class DossierMedical
{
    private $conn;
    private $table = "dossier_medical";

    public $id_dossier;
    public $description;
    public $date_creation;
    public $id_patient;
    public $id_medecin;

    // constructor
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ➕ CREATE dossier
    public function add()
    {
        $query = "INSERT INTO " . $this->table . "
        (id_dossier, description, date_creation, id_patient, id_medecin)
        VALUES (:id_dossier, :description, :date_creation, :id_patient, :id_medecin)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_dossier', $this->id_dossier);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':date_creation', $this->date_creation);
        $stmt->bindParam(':id_patient', $this->id_patient);
        $stmt->bindParam(':id_medecin', $this->id_medecin);

        return $stmt->execute();
    }

    // 📄 READ all dossiers
    public function getAll()
{
    $sql = "
        SELECT 
            d.*,
            p.Nom AS patient_name,
            m.Nom AS medecin_name
        FROM dossier_medical d
        JOIN user p ON d.id_patient = p.id_user
        JOIN user m ON d.id_medecin = m.id_user
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();

    return $stmt;
}

    // 🔍 READ one dossier
    public function getOne()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id_dossier = :id_dossier";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_dossier', $this->id_dossier);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✏️ UPDATE dossier
    public function update()
    {
        $query = "UPDATE " . $this->table . "
        SET description = :description,
            date_creation = :date_creation,
            id_patient = :id_patient,
            id_medecin = :id_medecin
        WHERE id_dossier = :id_dossier";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_dossier', $this->id_dossier);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':date_creation', $this->date_creation);
        $stmt->bindParam(':id_patient', $this->id_patient);
        $stmt->bindParam(':id_medecin', $this->id_medecin);

        return $stmt->execute();
    }

    // ❌ DELETE dossier
    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE id_dossier = :id_dossier";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_dossier', $this->id_dossier);

        return $stmt->execute();
    }
}