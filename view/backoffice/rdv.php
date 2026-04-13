<?php
require '../../config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = config::getConnexion();

if ($action === 'charger') {
    try {
       $sql = "SELECT rv.id_rdv,
               rv.date_demande,
               COALESCE(rv.date_rdv, c.date_creneau) AS date_rdv,
               rv.statut,
               rv.type_consultation,
               rv.id_patient,
               rv.id_creneau,
               rv.id_medecin,
               CONCAT(u.Prenom, ' ', u.Nom) AS nom_medecin
        FROM rendez_vous rv
        LEFT JOIN creneau c ON c.id_creneau = rv.id_creneau
        LEFT JOIN user u ON u.id_user = rv.id_medecin
        ORDER BY rv.date_demande DESC, rv.id_rdv DESC";
        $query = $db->prepare($sql);
        $query->execute();
        echo json_encode(["success" => true, "data" => $query->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'ajouter') {
    $id = $_POST['id'] ?? '';
    $patient = $_POST['patient'] ?? '';
    $date = $_POST['date_demande'] ?? '';
    $type = $_POST['type'] ?? '';
    $medecin = $_POST['medecin'] ?? '';
    $creneau = $_POST['creneau'] ?? '';

    if (!preg_match('/^RDV-[0-9]{3,}$/', $id)) {
        echo json_encode(["success" => false, "message" => "id_invalide"]);
        exit;
    }
    if ($patient === '' || $date === '' || $type === '' || $medecin === '' || $creneau === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }

    try {
        $query = $db->prepare("SELECT id_rdv FROM rendez_vous WHERE id_rdv = :id");
        $query->execute([':id' => $id]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(["success" => false, "message" => "id_existe"]);
            exit;
        }

        $query = $db->prepare("SELECT statut, id_medecin FROM creneau WHERE id_creneau = :creneau");
        $query->execute([':creneau' => $creneau]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(["success" => false, "message" => "creneau_introuvable"]);
            exit;
        }
        if (strtolower($row['statut']) === 'reserve') {
            echo json_encode(["success" => false, "message" => "creneau_pris"]);
            exit;
        }

        $sql = "INSERT INTO rendez_vous
                    (id_rdv, date_demande, date_rdv, statut, type_consultation, id_patient, id_creneau, id_medecin)
                VALUES
                    (:id, :date, NULL, 'en_attente', :type, :patient, :creneau, :medecin)";
        $query = $db->prepare($sql);
        $query->execute([
            ':id' => $id,
            ':date' => $date,
            ':type' => $type,
            ':patient' => $patient,
            ':creneau' => $creneau,
            ':medecin' => $medecin
        ]);

        $query = $db->prepare("UPDATE creneau SET statut = 'reserve' WHERE id_creneau = :creneau");
        $query->execute([':creneau' => $creneau]);

        echo json_encode(["success" => true, "message" => "RDV ajouté"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'modifier') {
    $id = $_POST['id'] ?? '';
    $date = $_POST['date'] ?? '';
    $type = $_POST['type'] ?? '';

    if ($id === '' || $date === '' || $type === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }

    try {
        $sql = "UPDATE rendez_vous
                SET date_demande = :date, type_consultation = :type
                WHERE id_rdv = :id";
        $query = $db->prepare($sql);
        $query->execute([
            ':date' => $date,
            ':type' => $type,
            ':id' => $id
        ]);
        echo json_encode(["success" => true, "message" => "RDV modifié"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'supprimer') {
    $id = $_POST['id'] ?? '';
    if ($id === '') {
        echo json_encode(["success" => false, "message" => "ID manquant"]);
        exit;
    }

    try {
        $query = $db->prepare("SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id");
        $query->execute([':id' => $id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(["success" => false, "message" => "RDV introuvable"]);
            exit;
        }

        $query = $db->prepare("DELETE FROM rendez_vous WHERE id_rdv = :id");
        $query->execute([':id' => $id]);

        $query = $db->prepare("UPDATE creneau SET statut = 'disponible' WHERE id_creneau = :creneau");
        $query->execute([':creneau' => $row['id_creneau']]);

        echo json_encode(["success" => true, "message" => "RDV supprimé"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Action inconnue"]);
}
