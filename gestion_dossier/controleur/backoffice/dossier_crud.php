<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Dossier.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── PING ─────────────────────────────────────────────────────────────────
    case 'ping':
        try {
            $db   = config::getConnexion();
            $cols = $db->query("DESCRIBE dossier_medical")->fetchAll();
            echo json_encode([
                'success' => true,
                'message' => 'Connexion OK à la base jumeaunum',
                'columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── READ ALL ─────────────────────────────────────────────────────────────
    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        d.id_dossier,
                        d.description,
                        d.date_creation,
                        d.id_patient,
                        d.id_medecin,
                        CONCAT(p.nom, ' ', p.prenom) AS patient_nom_complet,
                        CONCAT(m.nom, ' ', m.prenom) AS medecin_nom_complet
                    FROM dossier_medical d
                    LEFT JOIN user p ON d.id_patient = p.id_user AND p.id_role = 1
                    LEFT JOIN user m ON d.id_medecin = m.id_user AND m.id_role = 3
                    ORDER BY d.date_creation DESC";
            $q = $db->prepare($sql);
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CHECK ID EXISTS ───────────────────────────────────────────────────────
    // Vérifie si un id_dossier existe déjà — utilisé par le dé aléatoire et la validation
    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM dossier_medical WHERE id_dossier = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET MAX ID (pour générer un ID aléatoire) ─────────────────────────────
    case 'getExistingIds':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_dossier FROM dossier_medical");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_dossier');
            echo json_encode(['success' => true, 'ids' => $ids]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET PATIENTS (id_role = 1) ────────────────────────────────────────────
    case 'getPatients':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_user, nom, prenom FROM user WHERE id_role = 1 ORDER BY nom, prenom"
            );
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET MÉDECINS (id_role = 3) ────────────────────────────────────────────
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

    // ── CREATE ────────────────────────────────────────────────────────────────
    case 'add':
        try {
            $db = config::getConnexion();

            $id = intval($_POST['id_dossier'] ?? 0);

            // Vérifier que l'ID n'existe pas déjà
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM dossier_medical WHERE id_dossier = :id");
            $chk->execute(['id' => $id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID $id existe déjà dans la base."]);
                break;
            }

            $dossier = new Dossier(
                $id,
                $_POST['description']   ?? '',
                $_POST['date_creation'] ?? date('Y-m-d'),
                $_POST['id_patient']    ?? null,
                $_POST['id_medecin']    ?? null
            );

            $sql = "INSERT INTO dossier_medical (id_dossier, description, date_creation, id_patient, id_medecin)
                    VALUES (:id_dossier, :description, :date_creation, :id_patient, :id_medecin)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_dossier'    => $dossier->getIdDossier(),
                'description'   => $dossier->getDescription(),
                'date_creation' => $dossier->getDateCreation(),
                'id_patient'    => $dossier->getIdPatient(),
                'id_medecin'    => $dossier->getIdMedecin()
            ]);
            echo json_encode(['success' => true, 'message' => 'Dossier médical créé avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE dossier_medical SET
                        description   = :description,
                        date_creation = :date_creation,
                        id_patient    = :id_patient,
                        id_medecin    = :id_medecin
                    WHERE id_dossier = :id_dossier";
            $q = $db->prepare($sql);
            $q->execute([
                'id_dossier'    => intval($_POST['id_dossier']),
                'description'   => $_POST['description']   ?? '',
                'date_creation' => $_POST['date_creation'] ?? date('Y-m-d'),
                'id_patient'    => $_POST['id_patient']    ?? null,
                'id_medecin'    => $_POST['id_medecin']    ?? null
            ]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' dossier(s) mis à jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM dossier_medical WHERE id_dossier = :id");
            $q->execute(['id' => intval($_POST['id_dossier'])]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' dossier(s) supprimé(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
