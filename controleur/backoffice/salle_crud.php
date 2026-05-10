<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Salle.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        s.id_salle,
                        s.numero,
                        s.statut,
                        s.id_medecin,
                        CONCAT(u.nom, ' ', u.prenom) AS medecin_nom_complet
                    FROM salle s
                    LEFT JOIN user u ON s.id_medecin = u.id_user AND u.id_role = 3
                    ORDER BY s.id_salle ASC";
            $q = $db->prepare($sql);
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getExistingIds':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_salle FROM salle");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_salle');
            echo json_encode(['success' => true, 'ids' => array_map('intval', $ids)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM salle WHERE id_salle = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getMedecins':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_user, nom, prenom FROM user WHERE id_role = 3 ORDER BY nom, prenom"
            );
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add':
        try {
            $db = config::getConnexion();

            $id = intval($_POST['id_salle'] ?? 0);

            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM salle WHERE id_salle = :id");
            $chk->execute(['id' => $id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID salle $id existe deja."]);
                break;
            }

            $salle = new Salle(
                $id,
                intval($_POST['numero']  ?? 0),
                $_POST['statut']         ?? 'disponible',
                !empty($_POST['id_medecin']) ? intval($_POST['id_medecin']) : null
            );

            $sql = "INSERT INTO salle (id_salle, numero, statut, id_medecin)
                    VALUES (:id_salle, :numero, :statut, :id_medecin)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_salle'   => $salle->getIdSalle(),
                'numero'     => $salle->getNumero(),
                'statut'     => $salle->getStatut(),
                'id_medecin' => $salle->getIdMedecin()
            ]);
            echo json_encode(['success' => true, 'message' => 'Salle ajoutee avec succes !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE salle SET
                        numero     = :numero,
                        statut     = :statut,
                        id_medecin = :id_medecin
                    WHERE id_salle = :id_salle";
            $q = $db->prepare($sql);
            $q->execute([
                'id_salle'   => intval($_POST['id_salle']),
                'numero'     => intval($_POST['numero'] ?? 0),
                'statut'     => $_POST['statut']     ?? 'disponible',
                'id_medecin' => !empty($_POST['id_medecin']) ? intval($_POST['id_medecin']) : null
            ]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' salle(s) mise(s) a jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM salle WHERE id_salle = :id");
            $q->execute(['id' => intval($_POST['id_salle'])]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' salle(s) supprimee(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
