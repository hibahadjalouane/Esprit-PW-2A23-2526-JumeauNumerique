<?php
/**
 * create_checkout_session.php
 * Chemin : gestion_paiement/controleur/frontoffice/create_checkout_session.php
 *
 * Reçoit id_facture en POST, crée une session Stripe Checkout via cURL,
 * et redirige le patient vers la page de paiement Stripe.
 * Pas besoin du SDK Stripe, juste cURL (déjà dispo dans XAMPP).
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';
$pdo = config::getConnexion();

$id_patient = (int) $_SESSION['user_id'];
$id_facture = (int) ($_POST['id_facture'] ?? 0);

if (!$id_facture) {
    die('Facture invalide.');
}

// Clé secrète Stripe (test mode)
$stripeSecretKey = 'sk_test_51TT74YCWRHNpYscdONALpVIvgDePwNNI5RHmxJ9pQ4rhbvVLkZ7AnKLy2eIDT7xYXhI5kuJ3cD31BVQi47Aw1F0n00gIUWIJ7p';

// Récupère les infos de la facture (vérification appartenance + statut)
try {
    $stmt = $pdo->prepare("
        SELECT f.id_facture, f.montant, tp.nom_type
        FROM facture f
        JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
        WHERE f.id_facture  = :id_facture
          AND f.id_patient  = :id_patient
          AND f.statut      = 'Non payee'
        LIMIT 1
    ");
    $stmt->execute([':id_facture' => $id_facture, ':id_patient' => $id_patient]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur base de données : ' . $e->getMessage());
}

if (!$facture) {
    die('Facture introuvable ou déjà réglée.');
}

// Stripe travaille en centimes (USD ici car TND non supporté par Stripe Checkout)
// On affiche TND dans l'interface mais on envoie USD à Stripe en test mode
$montantCentimes = (int) round((float) $facture['montant'] * 100);

// URLs de retour après paiement
$baseUrl    = 'http://localhost/Esprit-PW-2A23-2526-JumeauNumerique';
$successUrl = $baseUrl . '/gestion_paiement/controleur/frontoffice/paiement_success.php?session_id={CHECKOUT_SESSION_ID}&id_facture=' . $id_facture;
$cancelUrl  = $baseUrl . '/gestion_paiement/vue/frontoffice/mes_factures.php?annule=1';

// Données envoyées à Stripe
$data = [
    'mode'        => 'payment',
    'success_url' => $successUrl,
    'cancel_url'  => $cancelUrl,
    'line_items[0][price_data][currency]'               => 'usd',
    'line_items[0][price_data][product_data][name]'     => 'JumeauNum - ' . $facture['nom_type'],
    'line_items[0][price_data][unit_amount]'            => $montantCentimes,
    'line_items[0][quantity]'                           => 1,
    'metadata[id_facture]'                              => $id_facture,
    'metadata[id_patient]'                              => $id_patient,
];

// Appel API Stripe via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD,        $stripeSecretKey . ':');
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_POSTFIELDS,     http_build_query($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Garde toujours à true en production

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $err = curl_error($ch);
    curl_close($ch);
    die('Erreur cURL : ' . $err);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode !== 200 || !isset($result['url'])) {
    echo '<pre>';
    var_dump($result);
    echo '</pre>';
    die('Echec de la création de session Stripe.');
}

// Redirige le patient vers la page de paiement Stripe (hébergée par Stripe)
header('Location: ' . $result['url']);
exit;