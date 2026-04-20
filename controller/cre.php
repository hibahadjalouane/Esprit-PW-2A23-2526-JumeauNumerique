<?php
require '../config.php';

class CreneauC
{
    // ── ADD ──────────────────────────────────────────────────
    function addCreneau($creneau)
    {
        $sql = "INSERT INTO creneau
                VALUES (:id, :dateCreneau, :heureDebut, :heureFin, :statut, :idMedecin)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id'          => $creneau->getIdCreneau(),
                'dateCreneau' => $creneau->getDateCreneau(),
                'heureDebut'  => $creneau->getHeureDebut(),
                'heureFin'    => $creneau->getHeureFin(),
                'statut'      => $creneau->getStatut(),
                'idMedecin'   => $creneau->getIdMedecin(),
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    // ── UPDATE ───────────────────────────────────────────────
    function updateCreneau($creneau, $id)
    {
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                'UPDATE creneau SET
                    date_creneau = :dateCreneau,
                    heure_debut  = :heureDebut,
                    heure_fin    = :heureFin,
                    statut       = :statut,
                    id_medecin   = :idMedecin
                WHERE id_creneau = :id'
            );
            $query->execute([
                'id'          => $id,
                'dateCreneau' => $creneau->getDateCreneau(),
                'heureDebut'  => $creneau->getHeureDebut(),
                'heureFin'    => $creneau->getHeureFin(),
                'statut'      => $creneau->getStatut(),
                'idMedecin'   => $creneau->getIdMedecin(),
            ]);
            echo $query->rowCount() . " records UPDATED successfully <br>";
        } catch (PDOException $e) {
            $e->getMessage();
        }
    }

    // ── DELETE ───────────────────────────────────────────────
    function deleteCreneau($ide)
    {
        $sql = "DELETE FROM creneau WHERE id_creneau = :id";
        $db  = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $ide);
        try {
            $req->execute();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    // ── SELECT ALL ───────────────────────────────────────────
    function getAllCreneau()
    {
        $sql = "SELECT * FROM creneau";
        $db  = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll();
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
// ================= API =================
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $db = config::getConnexion();

    if ($_GET['action'] === 'medecins') {
        try {
            $sql = "SELECT id_user, CONCAT(Nom, ' ', Prenom) AS nom_complet 
                    FROM user 
WHERE id_role = 'ROLE-MED' AND LOWER(Statut_Cmpt) = 'actif'
            $query = $db->prepare($sql);
            $query->execute();
            $data = $query->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true,
                "data" => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>