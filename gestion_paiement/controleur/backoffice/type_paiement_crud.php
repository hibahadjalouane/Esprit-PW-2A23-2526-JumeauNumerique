<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/TypePaiement.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // GET ALL types de paiement
    case 'getAll':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT * FROM type_paiement ORDER BY nom_type");
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // GET IDS EXISTANTS pour le de aleatoire
    case 'getExistingIds':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_type FROM type_paiement");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_type');
            echo json_encode(['success' => true, 'ids' => array_map('intval', $ids)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // CHECK ID : verifie si un id_type existe deja
    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM type_paiement WHERE id_type = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // CREATE type de paiement
    case 'add':
        try {
            $db = config::getConnexion();

            $id = intval($_POST['id_type'] ?? 0);

            // Verifier unicite de l'ID
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM type_paiement WHERE id_type = :id");
            $chk->execute(['id' => $id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID $id existe deja."]);
                break;
            }

            $tp = new TypePaiement(
                $id,
                trim($_POST['nom_type']    ?? ''),
                trim($_POST['description'] ?? ''),
                floatval($_POST['montant'] ?? 0)
            );

            $sql = "INSERT INTO type_paiement (id_type, nom_type, description, montant)
                    VALUES (:id_type, :nom_type, :description, :montant)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_type'     => $tp->getIdType(),
                'nom_type'    => $tp->getNomType(),
                'description' => $tp->getDescription(),
                'montant'     => $tp->getMontant()
            ]);
            echo json_encode(['success' => true, 'message' => 'Type de paiement ajoute avec succes !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // UPDATE type de paiement
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE type_paiement SET
                        nom_type    = :nom_type,
                        description = :description,
                        montant     = :montant
                    WHERE id_type = :id_type";
            $q = $db->prepare($sql);
            $q->execute([
                'id_type'     => intval($_POST['id_type']),
                'nom_type'    => trim($_POST['nom_type']    ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'montant'     => floatval($_POST['montant'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' type(s) mis a jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // DELETE type de paiement
    case 'delete':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM type_paiement WHERE id_type = :id");
            $q->execute(['id' => intval($_POST['id_type'])]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' type(s) supprime(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
