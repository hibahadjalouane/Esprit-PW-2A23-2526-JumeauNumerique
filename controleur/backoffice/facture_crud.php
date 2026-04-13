<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/Facture.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── DEBUG: test la connexion ─────────────────────────────────────────────
    // Appelé par: fetch('...facture_crud.php?action=ping')
    case 'ping':
        try {
            $db = config::getConnexion();
            // Lister les colonnes de la table user pour adapter la requête
            $cols = $db->query("DESCRIBE user")->fetchAll();
            echo json_encode([
                'success'      => true,
                'message'      => 'Connexion OK à la base jumeaunum',
                'user_columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── READ: tous les factures ──────────────────────────────────────────────
    case 'getAll':
        try {
            $db  = config::getConnexion();
            // Jointure avec user et type_paiement
            // On utilise COALESCE pour gérer différents noms de colonnes possibles
            $sql = "SELECT 
                        f.id_facture,
                        f.montant,
                        f.statut,
                        f.date_facture,
                        f.id_patient,
                        f.id_rdv,
                        f.id_type_paiement,
                        f.id_ligneOrd,
                        f.ressource_assignee,
                        CONCAT(u.nom, ' ', u.prenom) AS patient_nom_complet,
                        u.nom        AS patient_nom,
                        u.prenom     AS patient_prenom,
                        tp.nom_type,
                        tp.montant   AS type_montant
                    FROM facture f
                    LEFT JOIN user          u  ON f.id_patient       = u.id_user
                    LEFT JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
                    ORDER BY f.date_facture DESC";
            $query = $db->prepare($sql);
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
            $sql = "INSERT INTO facture
                        (id_facture, montant, statut, date_facture,
                         id_patient, id_rdv, id_type_paiement, id_ligneOrd, ressource_assignee)
                    VALUES
                        (:id_facture, :montant, :statut, :date_facture,
                         :id_patient, :id_rdv, :id_type_paiement, :id_ligneOrd, :ressource_assignee)";

            $facture = new Facture(
                $_POST['id_facture'],
                $_POST['montant'],
                $_POST['statut'],
                $_POST['date_facture'],
                $_POST['id_patient'],
                !empty($_POST['id_rdv'])              ? $_POST['id_rdv']              : null,
                $_POST['id_type_paiement'],
                !empty($_POST['id_ligneOrd'])          ? $_POST['id_ligneOrd']         : null,
                !empty($_POST['ressource_assignee'])   ? $_POST['ressource_assignee']  : null
            );

            $query = $db->prepare($sql);
            $query->execute([
                'id_facture'         => $facture->getIdFacture(),
                'montant'            => $facture->getMontant(),
                'statut'             => $facture->getStatut(),
                'date_facture'       => $facture->getDateFacture(),
                'id_patient'         => $facture->getIdPatient(),
                'id_rdv'             => $facture->getIdRdv(),
                'id_type_paiement'   => $facture->getIdTypePaiement(),
                'id_ligneOrd'        => $facture->getIdLigneOrd(),
                'ressource_assignee' => $facture->getRessourceAssignee()
            ]);
            echo json_encode(['success' => true, 'message' => 'Facture ajoutée avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE ───────────────────────────────────────────────────────────────
    case 'update':
        try {
            $db  = config::getConnexion();
            $sql = "UPDATE facture SET
                        montant            = :montant,
                        statut             = :statut,
                        date_facture       = :date_facture,
                        id_patient         = :id_patient,
                        id_rdv             = :id_rdv,
                        id_type_paiement   = :id_type_paiement,
                        id_ligneOrd        = :id_ligneOrd,
                        ressource_assignee = :ressource_assignee
                    WHERE id_facture = :id_facture";

            $query = $db->prepare($sql);
            $query->execute([
                'id_facture'         => $_POST['id_facture'],
                'montant'            => $_POST['montant'],
                'statut'             => $_POST['statut'],
                'date_facture'       => $_POST['date_facture'],
                'id_patient'         => $_POST['id_patient'],
                'id_rdv'             => !empty($_POST['id_rdv'])            ? $_POST['id_rdv']            : null,
                'id_type_paiement'   => $_POST['id_type_paiement'],
                'id_ligneOrd'        => !empty($_POST['id_ligneOrd'])       ? $_POST['id_ligneOrd']       : null,
                'ressource_assignee' => !empty($_POST['ressource_assignee'])? $_POST['ressource_assignee']: null
            ]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' facture(s) mise(s) à jour']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'delete':
        try {
            $db    = config::getConnexion();
            $query = $db->prepare("DELETE FROM facture WHERE id_facture = :id");
            $query->execute(['id' => $_POST['id_facture']]);
            echo json_encode(['success' => true, 'message' => $query->rowCount() . ' facture(s) supprimée(s)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET PATIENTS  (pour le dropdown) ────────────────────────────────────
    // On essaie d'abord id_user, sinon id (noms possibles selon le schéma)
    case 'getPatients':
        try {
            $db = config::getConnexion();

            // Détecter le nom de la colonne PK dans la table user
            $describeResult = $db->query("DESCRIBE user")->fetchAll();
            $columns        = array_column($describeResult, 'Field');

            // Choisir la colonne id
            if (in_array('id_user', $columns)) {
                $pkCol = 'id_user';
            } elseif (in_array('id', $columns)) {
                $pkCol = 'id';
            } else {
                // Prendre la première colonne (généralement la PK)
                $pkCol = $columns[0];
            }

            // Choisir les colonnes nom / prenom
            $nomCol    = in_array('nom', $columns)    ? 'nom'    : $pkCol;
            $prenomCol = in_array('prenom', $columns) ? 'prenom' : "''";

            $sql   = "SELECT $pkCol AS id_user, $nomCol AS nom, $prenomCol AS prenom FROM user ORDER BY $nomCol";
            $query = $db->prepare($sql);
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>