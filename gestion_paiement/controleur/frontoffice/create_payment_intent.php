<?php
/**
 * create_payment_intent.php
 * Chemin : gestion_paiement/controleur/frontoffice/create_payment_intent.php
 *
 * Reçoit un id_facture en POST, vérifie que la facture appartient au patient
 * connecté, puis crée un PaymentIntent Stripe et retourne le client_secret.
 */

header('Content-Type: application/json; charset=utf-8');

// Remonte de controleur/frontoffice → controleur → gestion_paiement → racine projet
require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';

// Charge l'autoloader Composer (Stripe SDK installé via composer)
// Le dossier vendor est à la racine du projet
require_once __DIR__ . '/../../../vendor/autoload.php';

$pdo = config::getConnexion();
$id_patient = (int) $_SESSION['user_id'];
$id_facture = (int) ($_POST['id_facture'] ?? 0);

if (!$id_facture) {
    echo json_encode(['success' => false, 'message' => 'Facture invalide.']);
    exit;
}

try {
    // Vérifie que la facture appartient au patient et n'est pas déjà payée
    $stmt = $pdo->prepare("
        SELECT f.id_facture, f.montant, tp.nom_type
        FROM facture f
        JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
        WHERE f.id_facture = :id_facture
          AND f.id_patient = :id_patient
          AND f.statut = 'Non payee'
        LIMIT 1
    ");
    $stmt->execute([':id_facture' => $id_facture, ':id_patient' => $id_patient]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facture) {
        echo json_encode(['success' => false, 'message' => 'Facture introuvable ou déjà réglée.']);
        exit;
    }

    // Clé secrète Stripe — stockée dans une variable d'environnement ou ici en constante
    // Pour production : utilise $_ENV['STRIPE_SECRET_KEY'] ou putenv()
    // Pour le dev local XAMPP, on peut la définir directement ici
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_51TT74YCWRHNpYscdONALpVIvgDePwNNI5RHmxJ9pQ4rhbvVLkZ7AnKLy2eIDT7xYXhI5kuJ3cD31BVQi47Aw1F0n00gIUWIJ7p';

    \Stripe\Stripe::setApiKey($stripeSecretKey);

    // Stripe travaille en centimes (entier), donc on multiplie par 100
    $montantCentimes = (int) round((float) $facture['montant'] * 100);

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'   => $montantCentimes,
        'currency' => 'usd', // Change en 'tnd' si Stripe supporte ta devise, sinon garde 'eur'
        'metadata' => [
            'id_facture' => $id_facture,
            'id_patient' => $id_patient,
            'nom_type'   => $facture['nom_type'],
        ],
        'payment_method_types' => ['card'],
        'description' => 'JumeauNum - Facture #' . $id_facture . ' - ' . $facture['nom_type'],
    ]);

    echo json_encode([
        'success'       => true,
        'client_secret' => $paymentIntent->client_secret,
        'montant'       => $facture['montant'],
        'nom_type'      => $facture['nom_type'],
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur Stripe : ' . $e->getMessage()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}