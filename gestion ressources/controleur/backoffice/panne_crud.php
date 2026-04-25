<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../modele/config.php';
require_once '../../modele/Panne.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'ping':
        try {
            $db = config::getConnexion();
            $cols = $db->query("DESCRIBE panne")->fetchAll();
            echo json_encode([
                'success' => true,
                'message' => 'Connexion OK',
                'panne_columns' => array_column($cols, 'Field')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getAll':
        try {
            $db = config::getConnexion();
            // Changement : resource -> ressources
            $sql = "SELECT 
                        p.id_Panne,
                        p.date_de_panne,
                        p.date_de_reparation,
                        p.statut,
                        p.description,
                        p.id_ressource,
                        r.Nom as ressource_nom
                    FROM panne p
                    LEFT JOIN ressources r ON p.id_ressource = r.id_ressource
                    ORDER BY p.date_de_panne DESC";
            $query = $db->prepare($sql);
            $query->execute();
            echo json_encode(['success' => true, 'data' => $query->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add':
        try {
            $db = config::getConnexion();

            if (!isset($_POST['id_Panne']) || !isset($_POST['id_ressource']) || !isset($_POST['date_de_panne']) || !isset($_POST['statut']) || !isset($_POST['description'])) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
                break;
            }

            $check = $db->prepare("SELECT COUNT(*) FROM panne WHERE id_Panne = :id");
            $check->execute(['id' => $_POST['id_Panne']]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet ID de panne existe déjà.']);
                break;
            }

            // Changement : resource -> ressources
            $checkRessource = $db->prepare("SELECT COUNT(*) FROM ressources WHERE id_ressource = :id");
            $checkRessource->execute(['id' => $_POST['id_ressource']]);
            if ($checkRessource->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'error' => 'Ressource non trouvée.']);
                break;
            }

            $panne = new Panne(
                $_POST['id_Panne'],
                $_POST['date_de_panne'],
                $_POST['date_de_reparation'] ?? null,
                $_POST['statut'],
                $_POST['description'],
                $_POST['id_ressource']
            );

            $sql = "INSERT INTO panne (id_Panne, date_de_panne, date_de_reparation, statut, description, id_ressource)
                    VALUES (:id_Panne, :date_de_panne, :date_de_reparation, :statut, :description, :id_ressource)";
            
            $query = $db->prepare($sql);
            $query->execute([
                'id_Panne' => $panne->getIdPanne(),
                'date_de_panne' => $panne->getDateDePanne(),
                'date_de_reparation' => $panne->getDateDeReparation(),
                'statut' => $panne->getStatut(),
                'description' => $panne->getDescription(),
                'id_ressource' => $panne->getIdRessource()
            ]);

            echo json_encode(['success' => true, 'message' => 'Panne déclarée avec succès !']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur SQL: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $db = config::getConnexion();

            if (!isset($_POST['id_Panne'])) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                break;
            }

            $sql = "UPDATE panne SET 
                        date_de_panne = :date_de_panne,
                        date_de_reparation = :date_de_reparation,
                        statut = :statut,
                        description = :description,
                        id_ressource = :id_ressource
                    WHERE id_Panne = :id_Panne";

            $query = $db->prepare($sql);
            $query->execute([
                'id_Panne' => $_POST['id_Panne'],
                'date_de_panne' => $_POST['date_de_panne'],
                'date_de_reparation' => $_POST['date_de_reparation'] ?? null,
                'statut' => $_POST['statut'],
                'description' => $_POST['description'],
                'id_ressource' => $_POST['id_ressource']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Panne mise à jour avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $db = config::getConnexion();
            
            if (!isset($_POST['id_Panne'])) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                break;
            }
            
            $query = $db->prepare("DELETE FROM panne WHERE id_Panne = :id");
            $query->execute(['id' => $_POST['id_Panne']]);
            
            echo json_encode(['success' => true, 'message' => 'Panne supprimée avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>