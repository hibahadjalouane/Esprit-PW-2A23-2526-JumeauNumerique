<?php
/**
 * login_process.php
 * Chemin : projetweb/gestion_users/controleur/frontoffice/login_process.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();

// ── Connexion BDD ─────────────────────────────────────────────────────────────
// Remonte jusqu'au modele du module gestion_users
require_once __DIR__ . '/../../modele/config.php';
$pdo = config::getConnexion();
// Ce fichier doit exposer une variable $pdo (PDO)

// ── Données POST ──────────────────────────────────────────────────────────────
$identifiant  = trim($_POST['identifiant']  ?? '');
$mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

if (empty($identifiant) || empty($mot_de_passe)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
    exit;
}

// ── Email ou username ? ───────────────────────────────────────────────────────
$column = str_contains($identifiant, '@') ? 'Email' : 'username';

// ── Requête BDD ───────────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare(
        "SELECT u.id_user, u.Nom, u.Prenom, u.Email, u.username,
                u.mot_de_passe, u.Statut_Cmpt, u.id_role, r.nom_role
         FROM user u
         JOIN role r ON u.id_role = r.id_role
         WHERE u.$column = :identifiant
         LIMIT 1"
    );
    $stmt->execute([':identifiant' => $identifiant]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.']);
    exit;
}

if ($mot_de_passe !== $user['mot_de_passe']) {
    echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.']);
    exit;
}

if (in_array($user['Statut_Cmpt'], ['bloque', 'inactif'])) {
    echo json_encode(['success' => false, 'message' => 'Compte désactivé. Contactez l\'administrateur.']);
    exit;
}

// ── Session ───────────────────────────────────────────────────────────────────
$_SESSION['user_id']     = $user['id_user'];
$_SESSION['user_nom']    = $user['Nom'];
$_SESSION['user_prenom'] = $user['Prenom'];
$_SESSION['user_email']  = $user['Email'];
$_SESSION['username']    = $user['username'];
$_SESSION['id_role']     = (int) $user['id_role'];
$_SESSION['nom_role']    = $user['nom_role'];
$_SESSION['logged_in']   = true;

// ── Redirection ───────────────────────────────────────────────────────────────
// Depuis controleur/frontoffice/ → remonter 2 niveaux → vue/
$roleId = (int) $user['id_role'];
$redirectMap = [
    1 => '../../vue/frontoffice/home.php',
    2 => '../../vue/backoffice/dashboard_admin.php',
    3 => '../../vue/backoffice/dashboard_medecin.php',
    4 => '../../vue/backoffice/dashboard_superadmin.php',
];
$redirect = $redirectMap[$roleId] ?? '../../vue/frontoffice/home.php';

echo json_encode(['success' => true, 'redirect' => $redirect, 'role' => $user['nom_role']]);
exit;