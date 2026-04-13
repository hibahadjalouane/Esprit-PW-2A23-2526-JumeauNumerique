<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/TypePaiement.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── READ ─────────────────────────────────────────────────────────────────
    case 'getAll':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare("SELECT * FROM type_paiement ORDER BY nom_type");
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CREATE ───────────────────────────────────────────────────────────────
    case 'add':
        try {
            $db  = config::getConnexion();
            $sql = "INSERT INTO type_paiement (id_type, nom_type, description, montant)
                    VALUES (:id_type, :nom_type, :description, :montant)";

            $tp = new TypePaiement(
                $_POST['id_type'],
                $_POST['nom_type'],
                $_POST['description'],
                $_POST['montant']
            );

            $query = $db->prepare($sql);
            $query->execute([
                'id_type'     => $tp->getIdType(),
                'nom_type'    => $tp->getNomType(),
                'description' => $tp->getDescription(),
                'montant'     => $tp->getMontant()
            ]);
            echo json_encode(['success' => true, 'message' => 'Type de paiement ajouté avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ───────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE type_paiement SET
                        nom_type    = :nom_type,
                        description = :description,
                        montant     = :montant
                    WHERE id_type = :id_type";

            $query = $db->prepare($sql);
            $query->execute([
                'id_type'     => $_POST['id_type'],
                'nom_type'    => $_POST['nom_type'],
                'description' => $_POST['description'],
                'montant'     => $_POST['montant']
            ]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' type(s) mis à jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare("DELETE FROM type_paiement WHERE id_type = :id");
            $query->execute(['id' => $_POST['id_type']]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' type(s) supprimé(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>