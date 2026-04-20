<?php
require '../../config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = config::getConnexion();

if ($action === 'medecins') {
    try {
        $sql = "SELECT id_user, CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,'')) AS nom_complet
                FROM user
                WHERE id_role = 'ROLE-MED'
                ORDER BY Prenom, Nom";
        $query = $db->prepare($sql);
        $query->execute();
        echo json_encode(["success" => true, "data" => $query->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'charger') {
    try {
        $sql = "SELECT id_creneau, date_creneau AS date, heure_debut, heure_fin, statut, id_medecin
                FROM creneau
                ORDER BY date_creneau ASC, heure_debut ASC";
        $query = $db->prepare($sql);
        $query->execute();
        echo json_encode(["success" => true, "data" => $query->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'charger_medecin') {
    $medecin = $_GET['medecin'] ?? '';
    if ($medecin === '') {
        echo json_encode(["success" => false, "message" => "ID médecin manquant"]);
        exit;
    }

    try {
        $sql = "SELECT id_creneau, date_creneau AS date, heure_debut, heure_fin, statut, id_medecin
                FROM creneau
                WHERE id_medecin = :medecin
                ORDER BY date_creneau ASC, heure_debut ASC";
        $query = $db->prepare($sql);
        $query->execute([':medecin' => $medecin]);
        echo json_encode(["success" => true, "creneaux" => $query->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'ajouter') {
    $date = $_POST['date'] ?? '';
    $debut = $_POST['debut'] ?? '';
    $fin = $_POST['fin'] ?? '';
    $medecin = $_POST['medecin'] ?? '';

    if ($date === '' || $debut === '' || $fin === '' || $medecin === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }
    if ($date < date('Y-m-d')) {
        echo json_encode(["success" => false, "message" => "date_invalide"]);
        exit;
    }
    if ($debut >= $fin) {
        echo json_encode(["success" => false, "message" => "heure_invalide"]);
        exit;
    }

    try {
        $sql = "SELECT id_creneau
                FROM creneau
                WHERE id_medecin = :medecin
                  AND date_creneau = :date
                  AND :debut < heure_fin
                  AND :fin > heure_debut";
        $query = $db->prepare($sql);
        $query->execute([
            ':medecin' => $medecin,
            ':date' => $date,
            ':debut' => $debut,
            ':fin' => $fin
        ]);

        if ($query->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(["success" => false, "message" => "creneau_existe"]);
            exit;
        }

        do {
            $id_creneau = 'CR-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $check = $db->prepare("SELECT id_creneau FROM creneau WHERE id_creneau = :id");
            $check->execute([':id' => $id_creneau]);
        } while ($check->fetch(PDO::FETCH_ASSOC));

        $sql = "INSERT INTO creneau (id_creneau, date_creneau, heure_debut, heure_fin, statut, id_medecin)
                VALUES (:id, :date, :debut, :fin, 'disponible', :medecin)";
        $query = $db->prepare($sql);
        $query->execute([
            ':id' => $id_creneau,
            ':date' => $date,
            ':debut' => $debut,
            ':fin' => $fin,
            ':medecin' => $medecin
        ]);

        echo json_encode(["success" => true, "message" => "Créneau ajouté", "id" => $id_creneau]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} elseif ($action === 'modifier') {
    $id = $_POST['id'] ?? '';
    $date = $_POST['date'] ?? '';
    $debut = $_POST['debut'] ?? '';
    $fin = $_POST['fin'] ?? '';

    if ($id === '' || $date === '' || $debut === '' || $fin === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }
    if ($date < date('Y-m-d')) {
        echo json_encode(["success" => false, "message" => "date_invalide"]);
        exit;
    }
    if ($debut >= $fin) {
        echo json_encode(["success" => false, "message" => "heure_invalide"]);
        exit;
    }

    try {
        $sql = "UPDATE creneau
                SET date_creneau = :date, heure_debut = :debut, heure_fin = :fin
                WHERE id_creneau = :id";
        $query = $db->prepare($sql);
        $query->execute([
            ':date' => $date,
            ':debut' => $debut,
            ':fin' => $fin,
            ':id' => $id
        ]);
        echo json_encode(["success" => true, "message" => "Créneau modifié"]);
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
        $query = $db->prepare("SELECT statut FROM creneau WHERE id_creneau = :id");
        $query->execute([':id' => $id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(["success" => false, "message" => "Créneau introuvable"]);
            exit;
        }
        if (strtolower($row['statut']) === 'reserve') {
            echo json_encode(["success" => false, "message" => "creneau_reserve"]);
            exit;
        }

        $query = $db->prepare("DELETE FROM creneau WHERE id_creneau = :id");
        $query->execute([':id' => $id]);
        echo json_encode(["success" => true, "message" => "Créneau supprimé"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Action inconnue"]);
}
