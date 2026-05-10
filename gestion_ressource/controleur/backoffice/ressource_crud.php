<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../modele/config.php';
require_once '../../modele/Ressource.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'ping':
        try {
            $db = config::getConnexion();
            // Changement : resource -> ressources
            $cols = $db->query("DESCRIBE ressources")->fetchAll();
            echo json_encode([
                'success' => true,
                'message' => 'Connexion OK',
                'resource_columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getAll':
        try {
            $db = config::getConnexion();
            // Changement : resource -> ressources
            $sql = "SELECT * FROM ressources ORDER BY id_ressource ASC";
            $query = $db->prepare($sql);
            $query->execute();
            $result = $query->fetchAll();
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add':
        try {
            $db = config::getConnexion();

            if (!isset($_POST['id_ressource']) || !isset($_POST['Nom']) || !isset($_POST['Type']) || !isset($_POST['Statut'])) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
                break;
            }

            // Changement : resource -> ressources
            $check = $db->prepare("SELECT COUNT(*) FROM ressources WHERE id_ressource = :id");
            $check->execute(['id' => $_POST['id_ressource']]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet ID de ressource existe déjà.']);
                break;
            }

            $ressource = new Ressource(
                $_POST['id_ressource'],
                $_POST['Nom'],
                $_POST['Type'],
                $_POST['Dernier_Maintenence'] ?? null,
                $_POST['Statut'],
                $_POST['Localisation'] ?? null
            );

            // Changement : resource -> ressources
            $sql = "INSERT INTO ressources (id_ressource, Nom, Type, Dernier_Maintenence, Statut, Localisation)
                    VALUES (:id_ressource, :Nom, :Type, :Dernier_Maintenence, :Statut, :Localisation)";
            
            $query = $db->prepare($sql);
            $query->execute([
                'id_ressource' => $ressource->getIdRessource(),
                'Nom' => $ressource->getNom(),
                'Type' => $ressource->getType(),
                'Dernier_Maintenence' => $ressource->getDernierMaintenence(),
                'Statut' => $ressource->getStatut(),
                'Localisation' => $ressource->getLocalisation()
            ]);

            echo json_encode(['success' => true, 'message' => 'Ressource ajoutée avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur SQL: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $db = config::getConnexion();

            if (!isset($_POST['id_ressource'])) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                break;
            }

            // Changement : resource -> ressources
            $sql = "UPDATE ressources SET 
                        Nom = :Nom,
                        Type = :Type,
                        Dernier_Maintenence = :Dernier_Maintenence,
                        Statut = :Statut,
                        Localisation = :Localisation
                    WHERE id_ressource = :id_ressource";

            $query = $db->prepare($sql);
            $query->execute([
                'id_ressource' => $_POST['id_ressource'],
                'Nom' => $_POST['Nom'],
                'Type' => $_POST['Type'],
                'Dernier_Maintenence' => $_POST['Dernier_Maintenence'] ?? null,
                'Statut' => $_POST['Statut'],
                'Localisation' => $_POST['Localisation'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Ressource mise à jour avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $db = config::getConnexion();
            
            if (!isset($_POST['id_ressource'])) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                break;
            }
            
            $db->beginTransaction();
            
            // Supprimer les pannes associées (table panne, pas de changement)
            $deletePannes = $db->prepare("DELETE FROM panne WHERE id_ressource = :id");
            $deletePannes->execute(['id' => $_POST['id_ressource']]);
            
            // Changement : resource -> ressources
            $deleteResource = $db->prepare("DELETE FROM ressources WHERE id_ressource = :id");
            $deleteResource->execute(['id' => $_POST['id_ressource']]);
            
            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'Ressource supprimée avec succès']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getStats':
        try {
            $db = config::getConnexion();
            $stats = [];
            
            // Changement : resource -> ressources
            $sql = "SELECT Statut, COUNT(*) as count FROM ressources GROUP BY Statut";
            $query = $db->prepare($sql);
            $query->execute();
            while ($row = $query->fetch()) {
                $stats[$row['Statut']] = $row['count'];
            }
            
            // Changement : resource -> ressources
            $sql = "SELECT COUNT(*) as total FROM ressources";
            $query = $db->prepare($sql);
            $query->execute();
            $stats['total'] = $query->fetch()['total'];
            
            $sql = "SELECT COUNT(*) as count FROM panne WHERE statut IN ('en_cours', 'en_attente')";
            $query = $db->prepare($sql);
            $query->execute();
            $stats['pannes_actives'] = $query->fetch()['count'];
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>