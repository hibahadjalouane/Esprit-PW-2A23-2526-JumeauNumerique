<?php
session_start();

$app_id     = '1669656207514097';
$app_secret = '1b9561d3fc25351e85b68ffd0ae6f4ef';
$redirect   = 'https://contently-sugar-projector.ngrok-free.dev/gestion_users/controleur/backoffice/oauth_facebook.php';
$code       = $_GET['code'] ?? '';

if (!$code) { header('Location: ../../vue/frontoffice/login.html'); exit; }

// Échanger le code contre un token
$tokenUrl = "https://graph.facebook.com/v18.0/oauth/access_token?client_id=$app_id&redirect_uri=" . urlencode($redirect) . "&client_secret=$app_secret&code=$code";
$tokenData = json_decode(file_get_contents($tokenUrl), true);
$access_token = $tokenData['access_token'] ?? '';

if (!$access_token) { header('Location: ../../vue/frontoffice/login.html?error=facebook'); exit; }

// Récupérer les infos utilisateur
$userUrl  = "https://graph.facebook.com/me?fields=id,name,email,first_name,last_name&access_token=$access_token";
$user     = json_decode(file_get_contents($userUrl), true);

$email  = $user['email']      ?? '';
$prenom = $user['first_name'] ?? '';
$nom    = $user['last_name']  ?? '';

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
    for ($i = 0; $i < 500; $i++) { $c = rand(1,99999999); if (!in_array($c,$ids)) { $newId=$c; break; } }

    $ins = $db->prepare("INSERT INTO user (id_user,nom,prenom,email,mot_de_passe,statut_cmpt,cin,service,id_role,oauth_provider,oauth_id) VALUES (:id,:nom,:prenom,:email,:mdp,'actif','','',1,'facebook',:oid)");
    $ins->execute(['id'=>$newId,'nom'=>$nom,'prenom'=>$prenom,'email'=>$email,'mdp'=>password_hash(bin2hex(random_bytes(16)),PASSWORD_BCRYPT),'oid'=>$user['id']]);

    $_SESSION['user_id']    = $newId;
    $_SESSION['user_role']  = 1;
    $_SESSION['user_name']  = $prenom . ' ' . $nom;
    $_SESSION['user_email'] = $email;
    $redirect = '../../vue/frontoffice/patient.html';
}

header('Location: ' . $redirect);
exit;
?>