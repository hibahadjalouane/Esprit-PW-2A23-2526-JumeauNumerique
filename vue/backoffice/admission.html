<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Consultation.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── READ ALL ─────────────────────────────────────────────────────────────
    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        c.id_consultation,
                        c.date_consultation,
                        c.motif,
                        c.diagnostic,
                        c.notes,
                        c.id_dossier
                    FROM consultation c
                    ORDER BY c.date_consultation DESC";
            $q = $db->prepare($sql);
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CHECK ID EXISTS ───────────────────────────────────────────────────────
    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM consultation WHERE id_consultation = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET EXISTING IDs ──────────────────────────────────────────────────────
    case 'getExistingIds':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_consultation FROM consultation");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_consultation');
            echo json_encode(['success' => true, 'ids' => $ids]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET DOSSIERS (pour le dropdown) ──────────────────────────────────────
    case 'getDossiers':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_dossier, description FROM dossier_medical ORDER BY id_dossier"
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

            $id = intval($_POST['id_consultation'] ?? 0);

            // Vérifier que l'ID n'existe pas déjà
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM consultation WHERE id_consultation = :id");
            $chk->execute(['id' => $id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID $id existe déjà dans la base."]);
                break;
            }

            $consultation = new Consultation(
                $id,
                $_POST['date_consultation'] ?? date('Y-m-d'),
                $_POST['motif']             ?? '',
                $_POST['diagnostic']        ?? '',
                $_POST['notes']             ?? '',
                intval($_POST['id_dossier'] ?? 0)
            );

            $sql = "INSERT INTO consultation (id_consultation, date_consultation, motif, diagnostic, notes, id_dossier)
                    VALUES (:id_consultation, :date_consultation, :motif, :diagnostic, :notes, :id_dossier)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_consultation'   => $consultation->getIdConsultation(),
                'date_consultation' => $consultation->getDateConsultation(),
                'motif'             => $consultation->getMotif(),
                'diagnostic'        => $consultation->getDiagnostic(),
                'notes'             => $consultation->getNotes(),
                'id_dossier'        => $consultation->getIdDossier()
            ]);
            echo json_encode(['success' => true, 'message' => 'Consultation créée avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE consultation SET
                        date_consultation = :date_consultation,
                        motif             = :motif,
                        diagnostic        = :diagnostic,
                        notes             = :notes,
                        id_dossier        = :id_dossier
                    WHERE id_consultation = :id_consultation";
            $q = $db->prepare($sql);
            $q->execute([
                'id_consultation'   => intval($_POST['id_consultation']),
                'date_consultation' => $_POST['date_consultation'] ?? date('Y-m-d'),
                'motif'             => $_POST['motif']             ?? '',
                'diagnostic'        => $_POST['diagnostic']        ?? '',
                'notes'             => $_POST['notes']             ?? '',
                'id_dossier'        => intval($_POST['id_dossier'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' consultation(s) mise(s) à jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM consultation WHERE id_consultation = :id");
            $q->execute(['id' => intval($_POST['id_consultation'])]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' consultation(s) supprimée(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
