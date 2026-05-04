<?php
/**
 * paiement_success.php
 * Chemin : gestion_paiement/controleur/frontoffice/paiement_success.php
 *
 * Stripe redirige ici après un paiement réussi.
 * Ce fichier :
 *   1. Vérifie le session_id auprès de Stripe via cURL (ne jamais faire confiance à l'URL seule)
 *   2. Met à jour la facture → 'payee'
 *   3. Crée le ticket numérique
 *   4. Envoie l'email de confirmation
 *   5. Redirige vers mes_factures.php avec le résultat en session
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';
require_once __DIR__ . '/../../modele/Ticket.php';
require_once __DIR__ . '/send_confirmation_email.php';

$pdo        = config::getConnexion();
$id_patient = (int) $_SESSION['user_id'];

$sessionId  = $_GET['session_id'] ?? '';
$id_facture = (int) ($_GET['id_facture'] ?? 0);

// Variables pour le résultat affiché à l'utilisateur
$paiement_ok = false;
$ticket_ok   = false;
$email_ok    = false;
$id_ticket   = null;
$date_expiration = null;
$erreur      = '';

if (!$sessionId || !$id_facture) {
    $erreur = 'Paramètres manquants.';
    stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);
}

$stripeSecretKey = 'sk_test_51TT74YCWRHNpYscdONALpVIvgDePwNNI5RHmxJ9pQ4rhbvVLkZ7AnKLy2eIDT7xYXhI5kuJ3cD31BVQi47Aw1F0n00gIUWIJ7p';

// Etape 1 : Vérifie la session Stripe côté serveur
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD,        $stripeSecretKey . ':');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$curlErr  = curl_errno($ch) ? curl_error($ch) : null;
curl_close($ch);

if ($curlErr) {
    $erreur = 'Erreur cURL lors de la vérification : ' . $curlErr;
    stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);
}

$session = json_decode($response, true);

// Vérifie que le paiement est bien confirmé par Stripe
if (!isset($session['payment_status']) || $session['payment_status'] !== 'paid') {
    $erreur = 'Paiement non confirmé par Stripe. Statut : ' . ($session['payment_status'] ?? 'inconnu');
    stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);
}

// Etape 2 : Met à jour la facture en BDD
try {
    // Vérifie que la facture appartient bien au patient connecté
    $stmt = $pdo->prepare("
        SELECT f.id_facture, f.montant, tp.nom_type,
               u.Email, u.Prenom, u.Nom
        FROM facture f
        JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
        JOIN user u ON f.id_patient = u.id_user
        WHERE f.id_facture = :id_facture
          AND f.id_patient = :id_patient
        LIMIT 1
    ");
    $stmt->execute([':id_facture' => $id_facture, ':id_patient' => $id_patient]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facture) {
        $erreur = 'Facture introuvable.';
        stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);
    }

    // Met à jour le statut (idempotent : si déjà payée, pas de problème)
    $update = $pdo->prepare("UPDATE facture SET statut = 'payee' WHERE id_facture = :id_facture AND statut = 'Non payee'");
    $update->execute([':id_facture' => $id_facture]);

    $paiement_ok = true;

} catch (PDOException $e) {
    $erreur = 'Erreur base de données : ' . $e->getMessage();
    stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);
}

// Etape 3 : Crée le ticket numérique
try {
    $ticketModel     = new Ticket($pdo);
    $ticketData      = $ticketModel->creerTicket($id_facture);
    $ticket_ok       = true;
    $id_ticket       = $ticketData['id_ticket'];
    $date_expiration = $ticketData['date_expiration'];
} catch (PDOException $e) {
    // Paiement ok mais ticket non créé — on continue quand même
    $ticket_ok = false;
}

// Etape 4 : Envoie l'email de confirmation
if ($ticket_ok && isset($ticketData)) {
    $destinataire = [
        'email'  => $facture['Email'],
        'prenom' => $facture['Prenom'],
        'nom'    => $facture['Nom'],
    ];
    $factureEmail = [
        'id_facture' => $facture['id_facture'],
        'montant'    => $facture['montant'],
        'nom_type'   => $facture['nom_type'],
    ];
    $emailResult = envoyerEmailConfirmation($destinataire, $factureEmail, $ticketData);
    $email_ok    = $emailResult['success'];
}

// Etape 5 : Stocke le résultat en session et redirige vers l'interface
stockerResultatEtRediriger($paiement_ok, $ticket_ok, $email_ok, $id_ticket, $date_expiration, $erreur);


/**
 * Stocke le résultat dans $_SESSION et redirige vers mes_factures.php
 */
function stockerResultatEtRediriger(bool $paiement_ok, bool $ticket_ok, bool $email_ok, ?int $id_ticket, ?string $date_expiration, string $erreur): never
{
    $_SESSION['paiement_resultat'] = [
        'paiement_ok'    => $paiement_ok,
        'ticket_ok'      => $ticket_ok,
        'email_ok'       => $email_ok,
        'id_ticket'      => $id_ticket,
        'date_expiration'=> $date_expiration,
        'erreur'         => $erreur,
    ];
    header('Location: /Esprit-PW-2A23-2526-JumeauNumerique/gestion_paiement/vue/frontoffice/mes_factures.php');
    exit;
}