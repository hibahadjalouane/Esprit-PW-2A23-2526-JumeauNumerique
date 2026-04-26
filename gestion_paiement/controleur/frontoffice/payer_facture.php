<?php
/**
 * payer_facture.php
 * Chemin : projetweb/gestion_des_paiements/controleur/frontoffice/payer_facture.php
 *
 * Met à jour le statut de la facture → 'payee'
 * (En prod tu brancheras Stripe ici avant de mettre à jour la BDD)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';
$pdo = config::getConnexion();

$id_facture = (int) ($_POST['id_facture'] ?? 0);
$id_patient = (int) $_SESSION['user_id'];

if (!$id_facture) {
    echo json_encode(['success' => false, 'message' => 'Facture invalide.']);
    exit;
}

try {
    // Vérifier que cette facture appartient bien au patient connecté
    // ET qu'elle est bien "Non payee" avant de la mettre à jour
    $stmt = $pdo->prepare("
        SELECT id_facture FROM facture
        WHERE id_facture = :id_facture
          AND id_patient = :id_patient
          AND statut = 'Non payee'
        LIMIT 1
    ");
    $stmt->execute([':id_facture' => $id_facture, ':id_patient' => $id_patient]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Facture introuvable ou déjà réglée.']);
        exit;
    }

    // Mettre à jour le statut
    $update = $pdo->prepare("
        UPDATE facture SET statut = 'payee' WHERE id_facture = :id_facture
    ");
    $update->execute([':id_facture' => $id_facture]);

    echo json_encode(['success' => true, 'message' => 'Paiement enregistré avec succès.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
