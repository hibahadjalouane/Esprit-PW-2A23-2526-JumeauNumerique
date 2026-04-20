<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';
require_once '../../modele/User.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── PING ─────────────────────────────────────────────────────────────────
    case 'ping':
        try {
            $db = config::getConnexion();
            echo json_encode(['success' => true, 'message' => 'Connexion OK']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── NOMBRE DE MÉDECINS (id_role = 3) pour affichage front ─────────────
    case 'countMedecins':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as total FROM user WHERE id_role = 3");
            $q->execute();
            echo json_encode(['success' => true, 'total' => (int)$q->fetch()['total']]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── VÉRIFIER SI EMAIL EXISTE DÉJÀ ────────────────────────────────────────
    case 'checkEmail':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE email = :email");
            $q->execute(['email' => $_GET['email'] ?? '']);
            echo json_encode(['success' => true, 'exists' => (int)$q->fetch()['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── VÉRIFIER SI CIN EXISTE DÉJÀ ──────────────────────────────────────────
    case 'checkCin':
        try {
            $db = config::getConnexion();
            // Si on est en mode édition, exclure l'utilisateur courant
            $excludeId = $_GET['exclude_id'] ?? null;
            if ($excludeId) {
                $q = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin = :cin AND id_user != :id");
                $q->execute(['cin' => $_GET['cin'] ?? '', 'id' => $excludeId]);
            } else {
                $q = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin = :cin");
                $q->execute(['cin' => $_GET['cin'] ?? '']);
            }
            echo json_encode(['success' => true, 'exists' => (int)$q->fetch()['cnt'] > 0]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GÉNÉRER UN ID ALÉATOIRE DISPONIBLE ───────────────────────────────────
    case 'genId':
        try {
            $db  = config::getConnexion();
            $q   = $db->prepare("SELECT id_user FROM user");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_user');
            $newId = null;
            for ($i = 0; $i < 500; $i++) {
                $candidate = rand(1, 99999999);
                if (!in_array($candidate, $ids)) { $newId = $candidate; break; }
            }
            echo json_encode(['success' => true, 'id' => $newId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── INSCRIPTION (SIGNUP) ──────────────────────────────────────────────────
    // id_role = 1 par défaut (patient)
    case 'signup':
        try {
            $db = config::getConnexion();

            $nom    = trim($_POST['nom']    ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email  = trim($_POST['email']  ?? '');
            $mdp    = $_POST['mot_de_passe'] ?? '';
            $cin    = trim($_POST['cin']    ?? '');
            $service = trim($_POST['service'] ?? '');

            // Générer ID unique
            $q   = $db->prepare("SELECT id_user FROM user");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_user');
            $newId = null;
            for ($i = 0; $i < 500; $i++) {
                $candidate = rand(1, 99999999);
                if (!in_array($candidate, $ids)) { $newId = $candidate; break; }
            }
            if (!$newId) {
                echo json_encode(['success' => false, 'error' => 'Impossible de générer un ID.']);
                break;
            }

            // Vérifier email unique
            $chkEmail = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE email = :email");
            $chkEmail->execute(['email' => $email]);
            if ((int)$chkEmail->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé.']);
                break;
            }

            // Vérifier CIN unique
            $chkCin = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin = :cin");
            $chkCin->execute(['cin' => $cin]);
            if ((int)$chkCin->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Ce CIN est déjà utilisé.']);
                break;
            }

            $user = new User(
                $newId, $nom, $prenom, $email,
                password_hash($mdp, PASSWORD_BCRYPT),
                'actif', $cin, $service, 1
            );

            $sql = "INSERT INTO user (id_user, nom, prenom, email, mot_de_passe, statut_cmpt, cin, service, id_role)
                    VALUES (:id_user, :nom, :prenom, :email, :mot_de_passe, :statut_cmpt, :cin, :service, :id_role)";
            $q = $db->prepare($sql);
            $q->execute([
                'id_user'      => $user->getIdUser(),
                'nom'          => $user->getNom(),
                'prenom'       => $user->getPrenom(),
                'email'        => $user->getEmail(),
                'mot_de_passe' => $user->getMdp(),
                'statut_cmpt'  => $user->getStatut(),
                'cin'          => $user->getCin(),
                'service'      => $user->getService(),
                'id_role'      => $user->getIdRole()
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Compte créé avec succès ! Bienvenue sur JumeauNum.',
                'id_user' => $newId
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET PROFIL (pour la page profil) ─────────────────────────────────────
    case 'getProfil':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_user, nom, prenom, email, statut_cmpt, cin, service, id_role FROM user WHERE id_user = :id");
            $q->execute(['id' => $_GET['id_user'] ?? 0]);
            $user = $q->fetch();
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── METTRE À JOUR LE PROFIL ───────────────────────────────────────────────
    case 'updateProfil':
        try {
            $db    = config::getConnexion();
            $idUser = intval($_POST['id_user'] ?? 0);

            // Vérifier email unique (exclure l'utilisateur courant)
            $chkEmail = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE email = :email AND id_user != :id");
            $chkEmail->execute(['email' => $_POST['email'] ?? '', 'id' => $idUser]);
            if ((int)$chkEmail->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé par un autre compte.']);
                break;
            }

            // Vérifier CIN unique (exclure l'utilisateur courant)
            $chkCin = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin = :cin AND id_user != :id");
            $chkCin->execute(['cin' => $_POST['cin'] ?? '', 'id' => $idUser]);
            if ((int)$chkCin->fetch()['cnt'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Ce CIN est déjà utilisé par un autre compte.']);
                break;
            }

            $sql = "UPDATE user SET nom=:nom, prenom=:prenom, email=:email, cin=:cin, service=:service WHERE id_user=:id";
            $q   = $db->prepare($sql);
            $q->execute([
                'nom'     => trim($_POST['nom']     ?? ''),
                'prenom'  => trim($_POST['prenom']  ?? ''),
                'email'   => trim($_POST['email']   ?? ''),
                'cin'     => trim($_POST['cin']     ?? ''),
                'service' => trim($_POST['service'] ?? ''),
                'id'      => $idUser
            ]);
            echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CHANGER MOT DE PASSE ─────────────────────────────────────────────────
    case 'changeMdp':
        try {
            $db     = config::getConnexion();
            $idUser = intval($_POST['id_user'] ?? 0);
            $ancien = $_POST['ancien_mdp'] ?? '';
            $nouveau = $_POST['nouveau_mdp'] ?? '';

            // Récupérer le hash actuel
            $q = $db->prepare("SELECT mot_de_passe FROM user WHERE id_user = :id");
            $q->execute(['id' => $idUser]);
            $row = $q->fetch();

            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable.']);
                break;
            }

            // Vérifier l'ancien mot de passe
            if (!password_verify($ancien, $row['mot_de_passe'])) {
                echo json_encode(['success' => false, 'error' => 'Ancien mot de passe incorrect.']);
                break;
            }

            // Vérifier que le nouveau est différent
            if (password_verify($nouveau, $row['mot_de_passe'])) {
                echo json_encode(['success' => false, 'error' => 'Le nouveau mot de passe doit être différent de l\'ancien.']);
                break;
            }

            $upd = $db->prepare("UPDATE user SET mot_de_passe = :mdp WHERE id_user = :id");
            $upd->execute(['mdp' => password_hash($nouveau, PASSWORD_BCRYPT), 'id' => $idUser]);
            echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── GET ALL USERS (superadmin) ────────────────────────────────────────────
    case 'getAllUsers':
        try {
            $db   = config::getConnexion();
            $role = $_GET['role'] ?? '';
            if ($role !== '') {
                $q = $db->prepare("SELECT id_user, nom, prenom, email, cin, service, statut_cmpt, id_role FROM user WHERE id_role = :role ORDER BY nom");
                $q->execute(['role' => intval($role)]);
            } else {
                $q = $db->prepare("SELECT id_user, nom, prenom, email, cin, service, statut_cmpt, id_role FROM user ORDER BY nom");
                $q->execute();
            }
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CHANGER RÔLE (superadmin) ─────────────────────────────────────────────
    case 'changeRole':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("UPDATE user SET id_role = :role WHERE id_user = :id");
            $q->execute(['role' => intval($_POST['id_role']), 'id' => intval($_POST['id_user'])]);
            echo json_encode(['success' => true, 'message' => 'Rôle mis à jour.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── CHANGER STATUT (superadmin — actif / bloqué) ──────────────────────────
    case 'changeStatut':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("UPDATE user SET statut_cmpt = :statut WHERE id_user = :id");
            $q->execute(['statut' => $_POST['statut'], 'id' => intval($_POST['id_user'])]);
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── SUPPRIMER UTILISATEUR (superadmin) ────────────────────────────────────
    case 'deleteUser':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM user WHERE id_user = :id");
            $q->execute(['id' => intval($_POST['id_user'])]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ── STATS (superadmin) ────────────────────────────────────────────────────
    case 'getStats':
        try {
            $db = config::getConnexion();
            $q  = $db->query("SELECT id_role, COUNT(*) as total FROM user GROUP BY id_role");
            $rows = $q->fetchAll();
            $stats = ['patients' => 0, 'medecins' => 0, 'admins' => 0, 'total' => 0];
            foreach ($rows as $r) {
                $stats['total'] += (int)$r['total'];
                if ($r['id_role'] == 1) $stats['patients'] = (int)$r['total'];
                if ($r['id_role'] == 3) $stats['medecins'] = (int)$r['total'];
                if ($r['id_role'] == 2) $stats['admins']   = (int)$r['total'];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue : "' . $action . '"']);
        break;
}
?>
