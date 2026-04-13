<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Admission.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── DEBUG: test la connexion ─────────────────────────────────────────────
    case 'ping':
        try {
            $db = config::getConnexion();
            $cols = $db->query("DESCRIBE admission")->fetchAll();
            echo json_encode([
                'success'           => true,
                'message'           => 'Connexion OK à la base jumeaunum',
                'admission_columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── READ: toutes les admissions ──────────────────────────────────────────
    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        a.id_admission,
                        a.date_arrive_relle,
                        a.mode_entree,
                        a.id_ticket,
                        t.statut AS ticket_statut
                    FROM admission a
                    LEFT JOIN ticket_num t ON a.id_ticket = t.id_ticket
                    ORDER BY a.date_arrive_relle DESC";
            $query = $db->prepare($sql);
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET TICKETS NON UTILISÉS (pour le dropdown) ──────────────────────────
    case 'getTickets':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                "SELECT id_ticket FROM ticket_num WHERE statut = 'non utilisé' ORDER BY id_ticket"
            );
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CREATE ───────────────────────────────────────────────────────────────
    case 'add':
        try {
            $db = config::getConnexion();

            // Vérifier que le ticket existe et est encore "non utilisé"
            $check = $db->prepare(
                "SELECT statut FROM ticket_num WHERE id_ticket = :id_ticket"
            );
            $check->execute(['id_ticket' => $_POST['id_ticket']]);
            $ticket = $check->fetch();

            if (!$ticket) {
                echo json_encode(['success' => false, 'error' => 'Ticket introuvable.']);
                break;
            }
            if ($ticket['statut'] !== 'non utilisé') {
                echo json_encode(['success' => false, 'error' => 'Ce ticket n\'est plus disponible (statut : ' . $ticket['statut'] . ').']);
                break;
            }

            $admission = new Admission(
                $_POST['id_admission'],
                $_POST['date_arrive_relle'],
                $_POST['mode_entree'],
                $_POST['id_ticket']
            );

            // Insérer l'admission
            $sql = "INSERT INTO admission (id_admission, date_arrive_relle, mode_entree, id_ticket)
                    VALUES (:id_admission, :date_arrive_relle, :mode_entree, :id_ticket)";
            $query = $db->prepare($sql);
            $query->execute([
                'id_admission'      => $admission->getIdAdmission(),
                'date_arrive_relle' => $admission->getDateArriveRelle(),
                'mode_entree'       => $admission->getModeEntree(),
                'id_ticket'         => $admission->getIdTicket()
            ]);

            // Marquer le ticket comme "utilisé"
            $upd = $db->prepare(
                "UPDATE ticket_num SET statut = 'utilisé' WHERE id_ticket = :id_ticket"
            );
            $upd->execute(['id_ticket' => $admission->getIdTicket()]);

            echo json_encode(['success' => true, 'message' => 'Admission ajoutée avec succès ! Ticket marqué comme utilisé.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ───────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db = config::getConnexion();

            // Récupérer l'ancien ticket de cette admission
            $old = $db->prepare("SELECT id_ticket FROM admission WHERE id_admission = :id");
            $old->execute(['id' => $_POST['id_admission']]);
            $oldRow = $old->fetch();
            $oldTicket = $oldRow ? $oldRow['id_ticket'] : null;

            $newTicket = $_POST['id_ticket'];

            // Si le ticket a changé, vérifier que le nouveau est "non utilisé"
            if ($oldTicket !== $newTicket) {
                $check = $db->prepare("SELECT statut FROM ticket_num WHERE id_ticket = :id_ticket");
                $check->execute(['id_ticket' => $newTicket]);
                $ticket = $check->fetch();

                if (!$ticket) {
                    echo json_encode(['success' => false, 'error' => 'Ticket introuvable.']);
                    break;
                }
                if ($ticket['statut'] !== 'non utilisé') {
                    echo json_encode(['success' => false, 'error' => 'Ce ticket n\'est plus disponible (statut : ' . $ticket['statut'] . ').']);
                    break;
                }

                // Remettre l'ancien ticket à "non utilisé"
                if ($oldTicket) {
                    $restore = $db->prepare("UPDATE ticket_num SET statut = 'non utilisé' WHERE id_ticket = :id_ticket");
                    $restore->execute(['id_ticket' => $oldTicket]);
                }

                // Marquer le nouveau ticket comme "utilisé"
                $upd = $db->prepare("UPDATE ticket_num SET statut = 'utilisé' WHERE id_ticket = :id_ticket");
                $upd->execute(['id_ticket' => $newTicket]);
            }

            $sql = "UPDATE admission SET
                        date_arrive_relle = :date_arrive_relle,
                        mode_entree       = :mode_entree,
                        id_ticket         = :id_ticket
                    WHERE id_admission = :id_admission";

            $query = $db->prepare($sql);
            $query->execute([
                'id_admission'      => $_POST['id_admission'],
                'date_arrive_relle' => $_POST['date_arrive_relle'],
                'mode_entree'       => $_POST['mode_entree'],
                'id_ticket'         => $newTicket
            ]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' admission(s) mise(s) à jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db = config::getConnexion();

            // Récupérer le ticket associé pour le remettre à "non utilisé"
            $old = $db->prepare("SELECT id_ticket FROM admission WHERE id_admission = :id");
            $old->execute(['id' => $_POST['id_admission']]);
            $oldRow = $old->fetch();

            $query = $db->prepare("DELETE FROM admission WHERE id_admission = :id");
            $query->execute(['id' => $_POST['id_admission']]);

            // Remettre le ticket à "non utilisé" si admission supprimée
            if ($oldRow && $oldRow['id_ticket']) {
                $restore = $db->prepare("UPDATE ticket_num SET statut = 'non utilisé' WHERE id_ticket = :id_ticket");
                $restore->execute(['id_ticket' => $oldRow['id_ticket']]);
            }

            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' admission(s) supprimée(s). Ticket remis à disponible.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
