<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Salle.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── PING ─────────────────────────────────────────────────────────────────
    case 'ping':
        try {
            $db   = config::getConnexion();
            $cols = $db->query("DESCRIBE salle")->fetchAll();
            echo json_encode([
                'success'       => true,
                'message'       => 'Connexion OK — table salle accessible',
                'salle_columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET ALL ──────────────────────────────────────────────────────────────
    case 'getAll':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                "SELECT s.id_salle, s.numero, s.statut, s.id_medecin,
                        CONCAT(u.Nom, ' ', u.Prenom) AS nom_medecin
                 FROM salle s
                 LEFT JOIN user u ON s.id_medecin = u.id_user
                 ORDER BY s.id_salle ASC"
            );
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET MÉDECINS (pour le dropdown) ──────────────────────────────────────
    case 'getMedecins':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                "SELECT id_user, CONCAT(Nom, ' ', Prenom) AS nom_complet
                 FROM user
                 WHERE id_role = 3
                 ORDER BY Nom ASC"
            );
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CREATE ───────────────────────────────────────────────────────────────
    case 'add':
        try {
            $db = config::getConnexion();

            // Vérifier doublon numero
            $chkNum = $db->prepare("SELECT id_salle FROM salle WHERE numero = :numero");
            $chkNum->execute(['numero' => $_POST['numero']]);
            if ($chkNum->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Ce numéro de salle existe déjà.']);
                break;
            }

            if (empty($_POST['id_medecin'])) {
                echo json_encode(['success' => false, 'error' => "Le médecin est obligatoire."]);
                break;
            }

            $salle = new Salle(
                trim($_POST['id_salle']),
                (int)$_POST['numero'],
                $_POST['statut'],
                (int)$_POST['id_medecin']
            );

            $sql = "INSERT INTO salle (id_salle, numero, statut, id_medecin)
                    VALUES (:id_salle, :numero, :statut, :id_medecin)";
            $query = $db->prepare($sql);
            $query->execute([
                'id_salle'   => $salle->getIdSalle(),
                'numero'     => $salle->getNumero(),
                'statut'     => $salle->getStatut(),
                'id_medecin' => $salle->getIdMedecin()
            ]);

            echo json_encode(['success' => true, 'message' => 'Salle ajoutée avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ───────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db = config::getConnexion();

            // Vérifier doublon numéro (sauf salle actuelle)
            $chkNum = $db->prepare("SELECT id_salle FROM salle WHERE numero = :numero AND id_salle != :id");
            $chkNum->execute(['numero' => (int)$_POST['numero'], 'id' => $_POST['id_salle']]);
            if ($chkNum->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Ce numéro est déjà utilisé par une autre salle.']);
                break;
            }

            if (empty($_POST['id_medecin'])) {
                echo json_encode(['success' => false, 'error' => "Le médecin est obligatoire."]);
                break;
            }

            $sql = "UPDATE salle SET numero = :numero, statut = :statut, id_medecin = :id_medecin
                    WHERE id_salle = :id_salle";
            $query = $db->prepare($sql);
            $query->execute([
                'id_salle'   => $_POST['id_salle'],
                'numero'     => (int)$_POST['numero'],
                'statut'     => $_POST['statut'],
                'id_medecin' => (int)$_POST['id_medecin']
            ]);

            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' salle(s) mise(s) à jour.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db = config::getConnexion();

            // Vérifier si la salle est liée à une admission
            $chk = $db->prepare("SELECT id_admission FROM admission WHERE id_salle = :id LIMIT 1");
            $chk->execute(['id' => $_POST['id_salle']]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Impossible de supprimer : salle liée à des admissions.']);
                break;
            }

            $query = $db->prepare("DELETE FROM salle WHERE id_salle = :id");
            $query->execute(['id' => $_POST['id_salle']]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' salle(s) supprimée(s).']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
