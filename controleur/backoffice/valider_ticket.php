<?php
/**
 * valider_ticket.php
 * Validation intelligente d'un ticket avant admission
 *
 * Actions disponibles (POST/GET) :
 *   valider_ticket  → vérifie existence, expiration, utilisation, puis marque utilisé
 *   check_ticket    → vérifie uniquement (sans marquer utilisé) — pour preview
 *   historique      → liste les dernières validations effectuées
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ─────────────────────────────────────────────────────────────────────────
   Utilitaire : vérification d'un ticket (sans commit)
   Retourne un tableau [ 'ok' => bool, 'code' => string, 'message' => string, 'ticket' => array|null ]
   ───────────────────────────────────────────────────────────────────────── */
function verifierTicket(PDO $db, string $idTicket): array
{
    // ① Existence
    $stmt = $db->prepare("
        SELECT id_ticket, statut, date_expiration
        FROM ticket_num
        WHERE id_ticket = :id
    ");
    $stmt->execute(['id' => $idTicket]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        return [
            'ok'      => false,
            'code'    => 'INTROUVABLE',
            'message' => "Ticket #$idTicket introuvable dans la base de données.",
            'ticket'  => null,
        ];
    }

    // ② Expiration (si la colonne date_expiration existe et est renseignée)
    if (!empty($ticket['date_expiration'])) {
        $expiry = new DateTime($ticket['date_expiration']);
        $now    = new DateTime();
        if ($now > $expiry) {
            return [
                'ok'      => false,
                'code'    => 'EXPIRE',
                'message' => "Ticket #$idTicket expiré depuis le " .
                             $expiry->format('d/m/Y à H:i') . '.',
                'ticket'  => $ticket,
            ];
        }
    }

    // ③ Déjà utilisé
    if ($ticket['statut'] === 'utilise') {
        return [
            'ok'      => false,
            'code'    => 'DEJA_UTILISE',
            'message' => "Ticket #$idTicket a déjà été utilisé.",
            'ticket'  => $ticket,
        ];
    }

    // ④ Statut inattendu (ni 'non utilise' ni 'utilise')
    if ($ticket['statut'] !== 'non utilise') {
        return [
            'ok'      => false,
            'code'    => 'STATUT_INVALIDE',
            'message' => "Ticket #$idTicket a un statut invalide : « {$ticket['statut']} ».",
            'ticket'  => $ticket,
        ];
    }

    // ✅ Tout est bon
    return [
        'ok'      => true,
        'code'    => 'VALIDE',
        'message' => "Ticket #$idTicket valide. Admission autorisée.",
        'ticket'  => $ticket,
    ];
}

/* ─────────────────────────────────────────────────────────────────────────
   Router
   ───────────────────────────────────────────────────────────────────────── */
switch ($action) {

    /* ── CHECK ONLY : prévisualisation sans marquer ──────────────────────── */
    case 'check_ticket':
        try {
            $db       = config::getConnexion();
            $idTicket = trim($_POST['id_ticket'] ?? $_GET['id_ticket'] ?? '');

            if ($idTicket === '') {
                echo json_encode(['success' => false, 'error' => 'id_ticket manquant.']);
                break;
            }

            $result = verifierTicket($db, $idTicket);
            echo json_encode([
                'success' => true,
                'valide'  => $result['ok'],
                'code'    => $result['code'],
                'message' => $result['message'],
                'ticket'  => $result['ticket'],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /* ── VALIDER : vérification + marquage utilisé (transaction atomique) ── */
    case 'valider_ticket':
        try {
            $db       = config::getConnexion();
            $idTicket = trim($_POST['id_ticket'] ?? '');

            if ($idTicket === '') {
                echo json_encode(['success' => false, 'error' => 'id_ticket manquant.']);
                break;
            }

            $db->beginTransaction();

            // Vérification dans la transaction pour éviter les races conditions
            $result = verifierTicket($db, $idTicket);

            if (!$result['ok']) {
                $db->rollBack();
                echo json_encode([
                    'success' => false,
                    'valide'  => false,
                    'code'    => $result['code'],
                    'message' => $result['message'],
                    'ticket'  => $result['ticket'],
                ]);
                break;
            }

            // ✅ Marquer le ticket comme utilisé
            $upd = $db->prepare("
                UPDATE ticket_num
                SET statut = 'utilise', date_utilisation = NOW()
                WHERE id_ticket = :id
            ");
            $upd->execute(['id' => $idTicket]);

            // Enregistrer dans l'historique de validation (si la table existe)
            try {
                $log = $db->prepare("
                    INSERT INTO validation_log (id_ticket, resultat, message, date_validation)
                    VALUES (:id_ticket, 'ACCEPTE', :message, NOW())
                ");
                $log->execute([
                    'id_ticket' => $idTicket,
                    'message'   => $result['message'],
                ]);
            } catch (Exception $ignored) {
                // Table optionnelle, on ignore si elle n'existe pas
            }

            // ── Notifier le patient dans l'application ───────────────────
            // Récupère id_admission + id_patient liés à ce ticket
            $admRow = null;
            try {
                $admStmt = $db->prepare("
                    SELECT id_admission, id_patient
                    FROM admission
                    WHERE id_ticket = :id_ticket
                    LIMIT 1
                ");
                $admStmt->execute(['id_ticket' => $idTicket]);
                $admRow = $admStmt->fetch();
            } catch (Exception $ignored) {}

            $notifInserted = false;
            if ($admRow && !empty($admRow['id_patient'])) {
                try {
                    $notif = $db->prepare("
                        INSERT INTO notifications
                            (id_user, type, titre, message, id_reference, date_creation, lu)
                        VALUES
                            (:id_user, 'ADMISSION_ACCEPTEE',
                             'Admission acceptée',
                             :message,
                             :id_reference,
                             NOW(), 0)
                    ");
                    $notif->execute([
                        'id_user'      => $admRow['id_patient'],
                        'message'      => "Votre admission #" . $admRow['id_admission'] .
                                          " a été acceptée. Ticket #$idTicket validé le " .
                                          date('d/m/Y à H:i') . ".",
                        'id_reference' => $admRow['id_admission'],
                    ]);
                    $notifInserted = true;
                } catch (Exception $ignored) {
                    // Table notifications optionnelle
                }
            }

            $db->commit();

            echo json_encode([
                'success'          => true,
                'valide'           => true,
                'code'             => 'ACCEPTE',
                'message'          => "✅ Admission acceptée. Ticket #$idTicket marqué comme utilisé.",
                'ticket'           => $result['ticket'],
                'id_admission'     => $admRow['id_admission']     ?? null,
                'id_patient'       => $admRow['id_patient']       ?? null,
                'notif_envoyee'    => $notifInserted,
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /* ── HISTORIQUE : dernières validations ──────────────────────────────── */
    case 'historique':
        try {
            $db = config::getConnexion();

            // Retourne les derniers tickets utilisés avec leur date d'utilisation
            $stmt = $db->prepare("
                SELECT
                    t.id_ticket,
                    t.statut,
                    t.date_expiration,
                    t.date_utilisation,
                    a.id_admission,
                    a.date_arrive_relle,
                    a.mode_entree
                FROM ticket_num t
                LEFT JOIN admission a ON a.id_ticket = t.id_ticket
                WHERE t.statut = 'utilise'
                ORDER BY t.date_utilisation DESC
                LIMIT 50
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /* ── NOTIFICATIONS : récupère les notifs d'un utilisateur ──────────── */
    case 'get_notifications':
        try {
            $db     = config::getConnexion();
            $idUser = intval($_GET['id_user'] ?? $_POST['id_user'] ?? 0);

            if (!$idUser) {
                echo json_encode(['success' => false, 'error' => 'id_user manquant.']);
                break;
            }

            $stmt = $db->prepare("
                SELECT id_notif, type, titre, message, id_reference, date_creation, lu
                FROM notifications
                WHERE id_user = :id_user
                ORDER BY date_creation DESC
                LIMIT 30
            ");
            $stmt->execute(['id_user' => $idUser]);
            $rows = $stmt->fetchAll();

            $nbNonLues = count(array_filter($rows, fn($r) => !$r['lu']));
            echo json_encode([
                'success'     => true,
                'data'        => $rows,
                'nb_non_lues' => $nbNonLues,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /* ── MARK READ : marque une ou toutes les notifs comme lues ─────────── */
    case 'mark_read':
        try {
            $db      = config::getConnexion();
            $idNotif = $_POST['id_notif'] ?? 'all';
            $idUser  = intval($_POST['id_user'] ?? 0);

            if ($idNotif === 'all' && $idUser) {
                $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE id_user = :id_user");
                $stmt->execute(['id_user' => $idUser]);
            } elseif (is_numeric($idNotif)) {
                $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE id_notif = :id");
                $stmt->execute(['id' => intval($idNotif)]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => "Action inconnue : \"$action\""]);
        break;
}
?>
