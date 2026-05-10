<?php
/**
 * logout_paiement.php
 * Chemin : projetweb/gestion_des_paiements/controleur/frontoffice/logout_paiement.php
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: /Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/frontoffice/login.php');
exit;
