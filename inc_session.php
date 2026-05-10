<?php
/**
 * inc_session.php  — Garde de session
 *
 * Ce fichier est à inclure EN PREMIER dans chaque interface PHP protégée.
 *
 * UTILISATION :
 * ─────────────────────────────────────────────────────────────────────────────
 *   <?php
 *   require_once 'chemin/vers/inc_session.php';
 *   checkSession();                   // vérifie juste que l'utilisateur est connecté
 *   // OU
 *   checkSession([2, 4]);             // vérifie que le rôle est admin (2) ou superadmin (4)
 *   ?>
 *
 * RÔLES (d'après ta table `role`) :
 *   1 → patient
 *   2 → admin
 *   3 → medecin
 *   4 → superadmin
 * ─────────────────────────────────────────────────────────────────────────────
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie la session et les droits d'accès.
 *
 * @param array $rolesAutorises  Tableau d'id_role autorisés.
 *                               Vide [] = n'importe quel utilisateur connecté.
 * @param string $redirectUrl    URL de redirection si accès refusé.
 */
function checkSession(array $rolesAutorises = [], string $redirectUrl = ''): void
{
    // ── 1. L'utilisateur est-il connecté ? ──────────────────────────────────
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        // Pas de session → redirection vers la page de connexion
        $loginUrl = determineLoginUrl();
        header('Location: ' . $loginUrl);
        exit;
    }

    // ── 2. Le rôle est-il autorisé ? ────────────────────────────────────────
    if (!empty($rolesAutorises)) {
        $roleActuel = (int) $_SESSION['id_role'];
        if (!in_array($roleActuel, $rolesAutorises, true)) {
            // Rôle insuffisant → redirection vers son propre dashboard
            $dashboard = getDashboardForRole($roleActuel);
            header('Location: ' . ($redirectUrl ?: $dashboard));
            exit;
        }
    }
}

/**
 * Détermine l'URL de la page login selon le chemin courant.
 * Fonctionne aussi bien depuis backoffice que frontoffice.
 */
function determineLoginUrl(): string
{
    // Chemin absolu vers la racine htdocs/projetapp
    // On remonte jusqu'à trouver le fichier login.html
    $currentDir = dirname($_SERVER['PHP_SELF']);
    // Chemin relatif simple (à adapter si ta structure diffère)
    return '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/frontoffice/login.php';
}

/**
 * Retourne l'URL du dashboard selon le rôle.
 */
function getDashboardForRole(int $roleId): string
{
    $map = [
        1 => '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/frontoffice/dashboard_patient.php',
        2 => '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/backoffice/dashboard_admin.php',
        3 => '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/backoffice/dashboard_medecin.php',
        4 => '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/backoffice/dashboard_superadmin.php',
    ];
    return $map[$roleId] ?? '/Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/frontoffice/dashboard_patient.php';
}

/**
 * Retourne les données de l'utilisateur connecté (raccourci pratique).
 */
function getCurrentUser(): array
{
    return [
        'id'      => $_SESSION['user_id']    ?? null,
        'nom'     => $_SESSION['user_nom']   ?? '',
        'prenom'  => $_SESSION['user_prenom'] ?? '',
        'email'   => $_SESSION['user_email'] ?? '',
        'id_role' => $_SESSION['id_role']    ?? null,
        'role'    => $_SESSION['nom_role']   ?? '',
    ];
}

/**
 * Déconnexion : détruit la session et redirige.
 */
function logout(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

    session_unset();
    session_destroy();
    header('Location: /Esprit-PW-2A23-2526-JumeauNumerique/gestion_user/vue/frontoffice/login.php');
    exit;
}
