<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Chemins depuis : gestion_admission/controleur/backoffice/
require_once '../../modele/config.php';
require_once '../../modele/Admission.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ─── TEST DE CONNEXION ────────────────────────────────────────────────────
    case 'ping':
        try {
            $db = config::getConnexion();
            $db->query("SELECT 1");
            echo json_encode(['success' => true, 'message' => 'Connexion OK a la base jumeaunum']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── RÉCUPÉRER TOUTES LES ADMISSIONS ─────────────────────────────────────
    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        a.id_admission,
                        a.date_arrive_relle,
                        a.mode_entree,
                        a.id_ticket,
                        a.id_salle,
                        a.id_patient,
                        t.statut AS ticket_statut,
                        s.numero AS salle_numero,
                        CONCAT(u.Nom, ' ', u.Prenom) AS patient_nom_complet
                    FROM admission a
                    LEFT JOIN ticket_num t ON a.id_ticket  = t.id_ticket
                    LEFT JOIN salle      s ON a.id_salle   = s.id_salle
                    LEFT JOIN user       u ON a.id_patient = u.id_user AND u.id_role = 1
                    ORDER BY a.date_arrive_relle DESC";
            $query = $db->prepare($sql);
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── IDs EXISTANTS (pour le dé aléatoire) ────────────────────────────────
    case 'getExistingIds':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_admission FROM admission");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_admission');
            echo json_encode(['success' => true, 'ids' => array_map('intval', $ids)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── VÉRIFIER SI UN ID EXISTE DÉJÀ ───────────────────────────────────────
    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM admission WHERE id_admission = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── RÉCUPÉRER LES TICKETS DISPONIBLES ───────────────────────────────────
    case 'getTickets':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                "SELECT id_ticket FROM ticket_num WHERE statut = 'non utilise' ORDER BY id_ticket"
            );
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── RÉCUPÉRER LES SALLES POUR LE DROPDOWN ───────────────────────────────
    case 'getSalles':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_salle, numero, statut FROM salle ORDER BY numero");
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── RÉCUPÉRER LES PATIENTS (id_role = 1) ────────────────────────────────
    case 'getPatients':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_user, Nom, Prenom, Email FROM user WHERE id_role = 1 ORDER BY Nom, Prenom"
            );
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── AJOUTER UNE ADMISSION ────────────────────────────────────────────────
    case 'add':
        try {
            $db = config::getConnexion();

            $id = intval($_POST['id_admission'] ?? 0);

            // Vérifier que l'ID n'existe pas déjà
            $chkId = $db->prepare("SELECT COUNT(*) as cnt FROM admission WHERE id_admission = :id");
            $chkId->execute(['id' => $id]);
            if ((int)$chkId->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID $id existe deja dans la base."]);
                break;
            }

            $idTicket  = !empty($_POST['id_ticket'])  ? $_POST['id_ticket']        : null;
            $idSalle   = !empty($_POST['id_salle'])   ? intval($_POST['id_salle'])  : null;
            $idPatient = !empty($_POST['id_patient']) ? intval($_POST['id_patient']): null;

            // Vérifier que le ticket est disponible
            if ($idTicket !== null) {
                $check = $db->prepare("SELECT statut FROM ticket_num WHERE id_ticket = :id_ticket");
                $check->execute(['id_ticket' => $idTicket]);
                $ticket = $check->fetch();

                if (!$ticket) {
                    echo json_encode(['success' => false, 'error' => 'Ticket introuvable.']);
                    break;
                }
                if ($ticket['statut'] !== 'non utilise') {
                    echo json_encode(['success' => false, 'error' => 'Ce ticket n\'est plus disponible (statut : ' . $ticket['statut'] . ').']);
                    break;
                }
            }

            $admission = new Admission(
                $id,
                $_POST['date_arrive_relle'],
                $_POST['mode_entree'],
                $idTicket,
                $idSalle
            );

            $sql = "INSERT INTO admission (id_admission, date_arrive_relle, mode_entree, id_ticket, id_salle, id_patient)
                    VALUES (:id_admission, :date_arrive_relle, :mode_entree, :id_ticket, :id_salle, :id_patient)";
            $query = $db->prepare($sql);
            $query->execute([
                'id_admission'      => $admission->getIdAdmission(),
                'date_arrive_relle' => $admission->getDateArriveRelle(),
                'mode_entree'       => $admission->getModeEntree(),
                'id_ticket'         => $admission->getIdTicket(),
                'id_salle'          => $admission->getIdSalle(),
                'id_patient'        => $idPatient
            ]);

            // Marquer le ticket comme utilisé
            if ($idTicket !== null) {
                $upd = $db->prepare("UPDATE ticket_num SET statut = 'utilise' WHERE id_ticket = :id_ticket");
                $upd->execute(['id_ticket' => $idTicket]);
            }

            // Récupérer le numéro de salle pour l'email
            $salleNumero = null;
            if ($idSalle !== null) {
                $qs = $db->prepare("SELECT numero FROM salle WHERE id_salle = :id");
                $qs->execute(['id' => $idSalle]);
                $salleRow    = $qs->fetch();
                $salleNumero = $salleRow ? $salleRow['numero'] : null;
            }

            // Récupérer l'email et le nom du patient si un patient est sélectionné
            $emailEnvoye = false;
            $emailErreur = null;
            if ($idPatient !== null) {
                $qp = $db->prepare("SELECT Nom, Prenom, Email FROM user WHERE id_user = :id AND id_role = 1");
                $qp->execute(['id' => $idPatient]);
                $patient = $qp->fetch();

                if ($patient && !empty($patient['Email'])) {
                    // Appeler le fichier d'envoi d'email
                    $emailData = [
                        'email_destinataire' => $patient['Email'],
                        'nom_patient'        => $patient['Nom'] . ' ' . $patient['Prenom'],
                        'id_admission'       => $id,
                        'date_arrive'        => $_POST['date_arrive_relle'],
                        'salle_numero'       => $salleNumero,
                        'id_ticket'          => $idTicket
                    ];

                    // Inclure et appeler la fonction d'envoi
                    require_once 'send_email_admission.php';
                    $resultEmail = envoyerEmailAdmission($emailData);
                    $emailEnvoye = $resultEmail['success'];
                    $emailErreur = $resultEmail['error'] ?? null;
                }
            }

            $msg = 'Admission ajoutee avec succes !';
            if ($idPatient !== null) {
                $msg .= $emailEnvoye
                    ? ' Email de confirmation envoye avec succes.'
                    : ' Attention : email non envoye (' . ($emailErreur ?? 'erreur inconnue') . ').';
            }

            echo json_encode([
                'success'       => true,
                'message'       => $msg,
                'email_envoye'  => $emailEnvoye,
                'email_erreur'  => $emailErreur
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── MODIFIER UNE ADMISSION ───────────────────────────────────────────────
    case 'update':
        try {
            $db = config::getConnexion();

            // Récupérer l'ancien ticket
            $old = $db->prepare("SELECT id_ticket FROM admission WHERE id_admission = :id");
            $old->execute(['id' => intval($_POST['id_admission'])]);
            $oldRow    = $old->fetch();
            $oldTicket = $oldRow ? $oldRow['id_ticket'] : null;
            $newTicket = !empty($_POST['id_ticket']) ? $_POST['id_ticket'] : null;

            if ($oldTicket !== $newTicket && $newTicket !== null) {
                $check = $db->prepare("SELECT statut FROM ticket_num WHERE id_ticket = :id_ticket");
                $check->execute(['id_ticket' => $newTicket]);
                $ticket = $check->fetch();

                if (!$ticket) {
                    echo json_encode(['success' => false, 'error' => 'Ticket introuvable.']);
                    break;
                }
                if ($ticket['statut'] !== 'non utilise') {
                    echo json_encode(['success' => false, 'error' => 'Ce ticket n\'est plus disponible (statut : ' . $ticket['statut'] . ').']);
                    break;
                }

                if ($oldTicket) {
                    $restore = $db->prepare("UPDATE ticket_num SET statut = 'non utilise' WHERE id_ticket = :id_ticket");
                    $restore->execute(['id_ticket' => $oldTicket]);
                }

                $upd = $db->prepare("UPDATE ticket_num SET statut = 'utilise' WHERE id_ticket = :id_ticket");
                $upd->execute(['id_ticket' => $newTicket]);
            }

            if ($newTicket === null && $oldTicket !== null) {
                $restore = $db->prepare("UPDATE ticket_num SET statut = 'non utilise' WHERE id_ticket = :id_ticket");
                $restore->execute(['id_ticket' => $oldTicket]);
            }

            $sql = "UPDATE admission SET
                        date_arrive_relle = :date_arrive_relle,
                        mode_entree       = :mode_entree,
                        id_ticket         = :id_ticket,
                        id_salle          = :id_salle,
                        id_patient        = :id_patient
                    WHERE id_admission = :id_admission";

            $query = $db->prepare($sql);
            $query->execute([
                'id_admission'      => intval($_POST['id_admission']),
                'date_arrive_relle' => $_POST['date_arrive_relle'],
                'mode_entree'       => $_POST['mode_entree'],
                'id_ticket'         => $newTicket,
                'id_salle'          => !empty($_POST['id_salle'])   ? intval($_POST['id_salle'])   : null,
                'id_patient'        => !empty($_POST['id_patient']) ? intval($_POST['id_patient']) : null
            ]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' admission(s) mise(s) a jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─── SUPPRIMER UNE ADMISSION ──────────────────────────────────────────────
    case 'delete':
        try {
            $db = config::getConnexion();

            $old = $db->prepare("SELECT id_ticket FROM admission WHERE id_admission = :id");
            $old->execute(['id' => intval($_POST['id_admission'])]);
            $oldRow = $old->fetch();

            $query = $db->prepare("DELETE FROM admission WHERE id_admission = :id");
            $query->execute(['id' => intval($_POST['id_admission'])]);

            // Remettre le ticket en disponible si suppression
            if ($oldRow && $oldRow['id_ticket']) {
                $restore = $db->prepare("UPDATE ticket_num SET statut = 'non utilise' WHERE id_ticket = :id_ticket");
                $restore->execute(['id_ticket' => $oldRow['id_ticket']]);
            }

            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' admission(s) supprimee(s).']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>