<?php
// controleur/backoffice/ai_proxy.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ==================== CONFIG ====================
// IMPORTANT: Remplacez par VOTRE propre clé API Gemini
// Obtenez une clé gratuite sur: https://aistudio.google.com/app/apikey
define('GEMINI_API_KEY', 'AIzaSyDQIacN5pmBCAXy9ehFKt-yXsgTfYFyO5U');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['messages'])) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$system = $input['system'] ?? '';
$messages = $input['messages'] ?? [];

// Récupérer le dernier message utilisateur
$lastUserMessage = '';
foreach (array_reverse($messages) as $msg) {
    if ($msg['role'] === 'user') {
        $lastUserMessage = $msg['content'];
        break;
    }
}

// Construire le prompt complet
$prompt = "";
if (!empty($system)) {
    $prompt .= $system . "\n\n";
}
$prompt .= "Question: " . $lastUserMessage . "\n\nRéponse:";

// Modèle à utiliser
$model = 'gemini-1.5-flash'; // ou 'gemini-pro'

// URL de l'API
$url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . GEMINI_API_KEY;

// Corps de la requête
$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 1024,
        'topP' => 0.95,
        'topK' => 40
    ]
];

// Appel API
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false // Pour test uniquement
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Gestion des erreurs
if ($curlError) {
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur CURL: ' . $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMsg = isset($errorData['error']['message']) 
        ? $errorData['error']['message'] 
        : "Erreur HTTP $httpCode";
    
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $text = $data['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode([
        'success' => true,
        'text' => $text
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Réponse inattendue de l\'API'
    ]);
}