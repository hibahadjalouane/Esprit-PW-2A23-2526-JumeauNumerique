<?php
/**
 * get_factures.php
 * Chemin : projetweb/gestion_des_paiements/controleur/frontoffice/get_factures.php
 *
 * Retourne en JSON les factures du patient connecté,
 * avec le nom du type de paiement (JOIN sur type_paiement).
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: application/json; charset=utf-8');

// Remonte de controleur/frontoffice → controleur → gestion_des_paiements → projetweb
require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]); // patients seulement

// Connexion BDD — utilise le modele de gestion_des_paiements
// Si tu n'as pas encore de config.php dans ce modele, pointe vers celui de gestion_users
require_once __DIR__ . '/../../modele/config.php';
$pdo = config::getConnexion();

$id_patient = (int) $_SESSION['user_id'];

try {
    // On joint type_paiement pour récupérer nom_type (= le titre affiché de la facture)
    $stmt = $pdo->prepare("
        SELECT
            f.id_facture,
            f.montant,
            f.statut,
            f.date_facture,
            f.id_rdv,
            f.id_type_paiement,
            f.id_ligneOrd,
            f.id_patient,
            tp.nom_type,
            tp.description AS type_description
        FROM facture f
        JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
        WHERE f.id_patient = :id_patient
        ORDER BY f.date_facture DESC
    ");
    $stmt->execute([':id_patient' => $id_patient]);
    $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculs pour les cartes de résumé
    $total_non_payees = 0;
    $nb_non_payees    = 0;
    $derniere_payee   = null;

    foreach ($factures as $f) {
        if ($f['statut'] === 'Non payee') {
            $total_non_payees += (float) $f['montant'];
            $nb_non_payees++;
        } elseif ($f['statut'] === 'payee' && $derniere_payee === null) {
            // La première facture payée dans l'ordre DESC date = la plus récente
            $derniere_payee = $f;
        }
    }

    echo json_encode([
        'success'          => true,
        'factures'         => $factures,
        'total_non_payees' => $total_non_payees,
        'nb_non_payees'    => $nb_non_payees,
        'derniere_payee'   => $derniere_payee,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
