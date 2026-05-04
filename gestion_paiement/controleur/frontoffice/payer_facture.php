<?php
/**
 * payer_facture.php  (VERSION MISE A JOUR)
 * Chemin : gestion_paiement/controleur/frontoffice/payer_facture.php
 *
 * Reçoit en POST le payment_intent_id confirmé par Stripe.js côté front,
 * vérifie son statut côté serveur, puis :
 *   1. Met à jour le statut de la facture → 'payee'
 *   2. Crée un ticket numérique dans ticket_num
 *   3. Envoie un email de confirmation au patient
 *
 * Retourne un JSON avec le détail de chaque étape.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../modele/config.php';
require_once __DIR__ . '/../../modele/Ticket.php';
require_once __DIR__ . '/send_confirmation_email.php';

$pdo        = config::getConnexion();
$id_patient = (int) $_SESSION['user_id'];

$id_facture        = (int) ($_POST['id_facture'] ?? 0);
$paymentIntentId   = trim($_POST['payment_intent_id'] ?? '');

// Réponse par défaut
$response = [
    'success'        => false,
    'paiement_ok'    => false,
    'ticket_ok'      => false,
    'email_ok'       => false,
    'message'        => '',
    'id_ticket'      => null,
    'date_expiration'=> null,
];

if (!$id_facture || !$paymentIntentId) {
    $response['message'] = 'Données manquantes.';
    echo json_encode($response);
    exit;
}

// Clé secrète Stripe
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_51TT74YCWRHNpYscdONALpVIvgDePwNNI5RHmxJ9pQ4rhbvVLkZ7AnKLy2eIDT7xYXhI5kuJ3cD31BVQi47Aw1F0n00gIUWIJ7p';
\Stripe\Stripe::setApiKey($stripeSecretKey);

try {
    // Vérification côté serveur du PaymentIntent (ne jamais faire confiance au front seul)
    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

    if ($paymentIntent->status !== 'succeeded') {
        $response['message'] = 'Paiement non confirmé par Stripe. Statut : ' . $paymentIntent->status;
        echo json_encode($response);
        exit;
    }

    // Vérifie que la facture appartient bien au patient et est non payée
    $stmt = $pdo->prepare("
        SELECT f.id_facture, f.montant, tp.nom_type,
               u.Email, u.Prenom, u.Nom
        FROM facture f
        JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
        JOIN user u ON f.id_patient = u.id_user
        WHERE f.id_facture = :id_facture
          AND f.id_patient = :id_patient
          AND f.statut = 'Non payee'
        LIMIT 1
    ");
    $stmt->execute([':id_facture' => $id_facture, ':id_patient' => $id_patient]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facture) {
        $response['message'] = 'Facture introuvable ou déjà réglée.';
        echo json_encode($response);
        exit;
    }

    // Etape 1 : Mise à jour du statut de la facture
    $update = $pdo->prepare("UPDATE facture SET statut = 'payee' WHERE id_facture = :id_facture");
    $update->execute([':id_facture' => $id_facture]);
    $response['paiement_ok'] = true;

    // Etape 2 : Création du ticket numérique
    $ticketData = null;
    try {
        $ticketModel = new Ticket($pdo);
        $ticketData  = $ticketModel->creerTicket($id_facture);
        $response['ticket_ok']      = true;
        $response['id_ticket']      = $ticketData['id_ticket'];
        $response['date_expiration']= $ticketData['date_expiration'];
    } catch (PDOException $e) {
        // Le paiement a réussi mais le ticket n'a pas pu être créé
        $response['ticket_ok'] = false;
        $response['ticket_error'] = 'Erreur ticket : ' . $e->getMessage();
    }

    // Etape 3 : Envoi de l'email de confirmation
    if ($ticketData) {
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
        $response['email_ok'] = $emailResult['success'];
        if (!$emailResult['success']) {
            $response['email_error'] = $emailResult['message'];
        }
    }

    // Message final selon les étapes réussies
    if ($response['paiement_ok'] && $response['ticket_ok']) {
        $response['success'] = true;
        $response['message'] = 'Paiement réussi. Ticket numérique généré.';
    } elseif ($response['paiement_ok'] && !$response['ticket_ok']) {
        $response['success'] = true; // paiement ok quand même
        $response['message'] = 'Paiement réussi. Ticket non généré — contactez le support.';
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    $response['message'] = 'Erreur Stripe : ' . $e->getMessage();
} catch (PDOException $e) {
    $response['message'] = 'Erreur base de données : ' . $e->getMessage();
}

echo json_encode($response);
