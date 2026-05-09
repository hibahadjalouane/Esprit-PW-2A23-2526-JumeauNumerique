<?php
session_start();

$client_id     = 'Ov23li6eW9ZmVudYXvg4';
$client_secret = 'f9e0c6b9d8ce38506792abd5693e082543bd80a8';
$code          = $_GET['code'] ?? '';

if (!$code) { header('Location: ../../vue/frontoffice/login.html'); exit; }

// Échanger le code contre un token
$response = file_get_contents("https://github.com/login/oauth/access_token?client_id=$client_id&client_secret=$client_secret&code=$code", false, stream_context_create([
    'http' => ['method' => 'GET', 'header' => 'Accept: application/json']
]));
$data = json_decode($response, true);
$access_token = $data['access_token'] ?? '';

if (!$access_token) { header('Location: ../../vue/frontoffice/login.html?error=github'); exit; }

// Récupérer les infos de l'utilisateur
$userJson = file_get_contents("https://api.github.com/user", false, stream_context_create([
    'http' => ['method' => 'GET', 'header' => "Authorization: Bearer $access_token\r\nUser-Agent: JumeauNum\r\nAccept: application/json"]
]));
$user = json_decode($userJson, true);

// Récupérer l'email si non public
$email = $user['email'] ?? '';
if (!$email) {
    $emailsJson = file_get_contents("https://api.github.com/user/emails", false, stream_context_create([
        'http' => ['method' => 'GET', 'header' => "Authorization: Bearer $access_token\r\nUser-Agent: JumeauNum\r\nAccept: application/json"]
    ]));
    $emails = json_decode($emailsJson, true);
    foreach ($emails as $e) {
        if ($e['primary'] && $e['verified']) { $email = $e['email']; break; }
    }
}

$name  = $user['name'] ?? $user['login'] ?? '';
$parts = explode(' ', trim($name), 2);
$prenom = $parts[0] ?? $name;
$nom    = $parts[1] ?? '';

// Enregistrer en session et rediriger
require_once '../../modele/config.php';
$db = config::getConnexion();

$q = $db->prepare("SELECT * FROM user WHERE email = :email");
$q->execute(['email' => $email]);
$existingUser = $q->fetch();

if ($existingUser) {
    $_SESSION['user_id']    = $existingUser['id_user'];
    $_SESSION['user_role']  = $existingUser['id_role'];
    $_SESSION['user_name']  = $existingUser['prenom'] . ' ' . $existingUser['nom'];
    $_SESSION['user_email'] = $existingUser['email'];
    if ($existingUser['id_role'] === 1) {
        $redirect = '../../vue/frontoffice/patient.html';
    } elseif ($existingUser['id_role'] === 3) {
        $redirect = '../../vue/frontoffice/medecin_dashboard.html';
    } else {
        $redirect = '../../vue/backoffice/supadmin.html';
    }
} else {
    $q2 = $db->prepare("SELECT id_user FROM user");
    $q2->execute();
    $ids = array_column($q2->fetchAll(), 'id_user');
    $newId = null;
    for ($i = 0; $i < 500; $i++) { $c = rand(1, 99999999); if (!in_array($c, $ids)) { $newId = $c; break; } }

    $ins = $db->prepare("INSERT INTO user (id_user,nom,prenom,email,mot_de_passe,statut_cmpt,cin,service,id_role,oauth_provider,oauth_id) VALUES (:id,:nom,:prenom,:email,:mdp,'actif','','',1,'github',:oid)");
    $ins->execute(['id' => $newId, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'mdp' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'oid' => $user['id']]);

    $_SESSION['user_id']    = $newId;
    $_SESSION['user_role']  = 1;
    $_SESSION['user_name']  = $prenom . ' ' . $nom;
    $_SESSION['user_email'] = $email;
    $redirect = '../../vue/frontoffice/patient.html';
}

header('Location: ' . $redirect);
exit;
?>