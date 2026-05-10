<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Facture.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // PING : teste la connexion a la BDD
    case 'ping':
        try {
            $db = config::getConnexion();
            $db->query("SELECT 1");
            echo json_encode(['success' => true, 'message' => 'Connexion OK a la base jumeaunum']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // GET ALL FACTURES avec jointures patients et types
    case 'getAll':
        try {
            $db  = config::getConnexion();
            $sql = "SELECT
                        f.id_facture,
                        f.montant,
                        f.statut,
                        f.date_facture,
                        f.id_rdv,
                        f.id_type_paiement,
                        f.id_ligneOrd,
                        f.id_patient,
                        f.id_ressource_assignee,
                        CONCAT(u.nom, ' ', u.prenom) AS patient_nom_complet,
                        u.nom        AS patient_nom,
                        u.prenom     AS patient_prenom,
                        tp.nom_type,
                        tp.montant   AS type_montant,
                        r.Nom        AS ressource_nom
                    FROM facture f
                    LEFT JOIN user           u  ON f.id_patient           = u.id_user
                    LEFT JOIN type_paiement  tp ON f.id_type_paiement     = tp.id_type
                    LEFT JOIN ressources     r  ON f.id_ressource_assignee = r.id_ressource
                    ORDER BY f.date_facture DESC";
            $q = $db->prepare($sql);
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
            $q  = $db->prepare("SELECT id_facture FROM facture");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_facture');
            echo json_encode(['success' => true, 'ids' => array_map('intval', $ids)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // CHECK ID : verifie si un id_facture existe deja
    case 'checkId':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM facture WHERE id_facture = :id");
            $q->execute(['id' => intval($_GET['id'] ?? 0)]);
            $row = $q->fetch();
            echo json_encode(['success' => true, 'exists' => (int)$row['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // GET PATIENTS pour le dropdown (tous les users)
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

    // GET RESSOURCES pour le dropdown id_ressource_assignee
    case 'getRessources':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_ressource, Nom, Type, Statut FROM ressources ORDER BY Nom"
            );
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // GET RDV pour le dropdown id_rdv
    case 'getRdvs':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare(
                "SELECT id_rdv, date_rdv, type_consultation, statut FROM rendez_vous ORDER BY date_rdv DESC"
            );
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // GET LIGNES ORDONNANCE filtrées par patient ET statut = Non payee
    case 'getLignesOrd':
        try {
            $db        = config::getConnexion();
            $idPatient = intval($_GET['id_patient'] ?? 0);

            if ($idPatient <= 0) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }

            // Connexion: ligne_ordonnance -> ordonnance -> patient
            // On filtre statut = 'Non payee' dans ligne_ordonnance
            $sql = "SELECT
                        lo.id_ligne,
                        lo.date_ordonnance,
                        lo.details,
                        lo.quantite,
                        lo.statut
                    FROM ligne_ordonnance lo
                    INNER JOIN ordonnance o ON lo.id_ordonnance = o.id_ordonnance
                    WHERE o.id_patient = :id_patient
                      AND lo.statut = 'Non payee'
                    ORDER BY lo.date_ordonnance DESC";

            $q = $db->prepare($sql);
            $q->execute(['id_patient' => $idPatient]);
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // CREATE facture
    case 'add':
        try {
            $db = config::getConnexion();

            $id = intval($_POST['id_facture'] ?? 0);

            // Verifier unicite de l'ID
            $chk = $db->prepare("SELECT COUNT(*) as cnt FROM facture WHERE id_facture = :id");
            $chk->execute(['id' => $id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => "L'ID $id existe deja dans la base."]);
                break;
            }

            $facture = new Facture(
                $id,
                floatval($_POST['montant']              ?? 0),
                $_POST['statut']                        ?? 'Non payee',
                $_POST['date_facture']                  ?? date('Y-m-d'),
                !empty($_POST['id_rdv'])                ? intval($_POST['id_rdv'])                : null,
                intval($_POST['id_type_paiement']       ?? 0),
                !empty($_POST['id_ligneOrd'])           ? intval($_POST['id_ligneOrd'])           : null,
                intval($_POST['id_patient']             ?? 0),
                !empty($_POST['id_ressource_assignee']) ? intval($_POST['id_ressource_assignee']) : null
            );

            $sql = "INSERT INTO facture
                        (id_facture, montant, statut, date_facture,
                         id_rdv, id_type_paiement, id_ligneOrd,
                         id_patient, id_ressource_assignee)
                    VALUES
                        (:id_facture, :montant, :statut, :date_facture,
                         :id_rdv, :id_type_paiement, :id_ligneOrd,
                         :id_patient, :id_ressource_assignee)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_facture'           => $facture->getIdFacture(),
                'montant'              => $facture->getMontant(),
                'statut'               => $facture->getStatut(),
                'date_facture'         => $facture->getDateFacture(),
                'id_rdv'               => $facture->getIdRdv(),
                'id_type_paiement'     => $facture->getIdTypePaiement(),
                'id_ligneOrd'          => $facture->getIdLigneOrd(),
                'id_patient'           => $facture->getIdPatient(),
                'id_ressource_assignee' => $facture->getIdRessourceAssignee()
            ]);
            echo json_encode(['success' => true, 'message' => 'Facture ajoutee avec succes !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // UPDATE facture (l'ID ne change pas)
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE facture SET
                        montant              = :montant,
                        statut               = :statut,
                        date_facture         = :date_facture,
                        id_rdv               = :id_rdv,
                        id_type_paiement     = :id_type_paiement,
                        id_ligneOrd          = :id_ligneOrd,
                        id_patient           = :id_patient,
                        id_ressource_assignee = :id_ressource_assignee
                    WHERE id_facture = :id_facture";
            $q = $db->prepare($sql);
            $q->execute([
                'id_facture'           => intval($_POST['id_facture']),
                'montant'              => floatval($_POST['montant']              ?? 0),
                'statut'               => $_POST['statut']                        ?? 'Non payee',
                'date_facture'         => $_POST['date_facture']                  ?? date('Y-m-d'),
                'id_rdv'               => !empty($_POST['id_rdv'])                ? intval($_POST['id_rdv'])                : null,
                'id_type_paiement'     => intval($_POST['id_type_paiement']       ?? 0),
                'id_ligneOrd'          => !empty($_POST['id_ligneOrd'])           ? intval($_POST['id_ligneOrd'])           : null,
                'id_patient'           => intval($_POST['id_patient']             ?? 0),
                'id_ressource_assignee' => !empty($_POST['id_ressource_assignee']) ? intval($_POST['id_ressource_assignee']) : null
            ]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' facture(s) mise(s) a jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // DELETE facture
    case 'delete':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM facture WHERE id_facture = :id");
            $q->execute(['id' => intval($_POST['id_facture'])]);
            echo json_encode(['success' => true, 'message' => $q->rowCount() . ' facture(s) supprimee(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
