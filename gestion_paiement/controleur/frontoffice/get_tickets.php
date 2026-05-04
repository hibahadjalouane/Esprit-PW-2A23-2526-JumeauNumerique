<?php
/**
 * get_tickets.php
 * Chemin : gestion_paiement/controleur/frontoffice/get_tickets.php
 *
 * Retourne en JSON tous les tickets du patient connecté.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';
require_once __DIR__ . '/../../modele/Ticket.php';

$pdo        = config::getConnexion();
$id_patient = (int) $_SESSION['user_id'];

try {
    $ticketModel = new Ticket($pdo);
    $tickets     = $ticketModel->getTicketsParPatient($id_patient);

    echo json_encode(['success' => true, 'tickets' => $tickets]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
