<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'yassinechaari52@gmail.com';
        $mail->Password   = 'deredckcdywzavvm';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('yassinechaari52@gmail.com', 'JumeauNum');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

require_once '../../modele/config.php';
require_once '../../modele/User.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function sendAlertSMS($phone, $message) {
    error_log("[SMS ALERT] To: $phone | Msg: $message");
    return true;
}

function sendAlertEmail($to, $subject, $body) {
    error_log("[EMAIL ALERT] To: $to | Subject: $subject");
    return true;
}

switch ($action) {

    case 'login':
        try {
            $db    = config::getConnexion();
            $email = trim($_POST['email'] ?? '');
            $mdp   = $_POST['mot_de_passe'] ?? '';
            $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            if (!$email || !$mdp) {
                echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis.']);
                break;
            }

            $chkAttempts = $db->prepare("SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = :ip AND email = :email AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND success = 0");
            $chkAttempts->execute(['ip' => $ip, 'email' => $email]);
            $attempts = (int)$chkAttempts->fetch()['cnt'];

            if ($attempts >= 5) {
                $ins = $db->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (:e,:ip,0,NOW())");
                $ins->execute(['e' => $email, 'ip' => $ip]);
                echo json_encode(['success' => false, 'error' => 'Trop de tentatives échouées. Veuillez patienter 15 minutes.', 'locked' => true, 'attempts' => $attempts]);
                break;
            }

            $q = $db->prepare("SELECT * FROM user WHERE email = :email");
            $q->execute(['email' => $email]);
            $user = $q->fetch();

            if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
                $ins = $db->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (:e,:ip,0,NOW())");
                $ins->execute(['e' => $email, 'ip' => $ip]);
                $remaining = 4 - $attempts;
                echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.', 'remaining' => max(0, $remaining), 'attempts' => $attempts + 1]);
                break;
            }

            if ($user['statut_cmpt'] === 'bloqué' || $user['statut_cmpt'] === 'bloque') {
                echo json_encode(['success' => false, 'error' => 'Votre compte a été suspendu par un administrateur. Contactez le support.', 'banned' => true]);
                break;
            }

            $ins = $db->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (:e,:ip,1,NOW())");
            $ins->execute(['e' => $email, 'ip' => $ip]);
            $upd = $db->prepare("UPDATE user SET last_login = NOW() WHERE id_user = :id");
            $upd->execute(['id' => $user['id_user']]);

            $_SESSION['user_id']    = $user['id_user'];
            $_SESSION['user_role']  = $user['id_role'];
            $_SESSION['user_name']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_email'] = $user['email'];

            $del = $db->prepare("DELETE FROM login_attempts WHERE email=:e AND success=0");
            $del->execute(['e' => $email]);

            echo json_encode(['success' => true, 'message' => 'Connexion réussie. Bienvenue ' . $user['prenom'] . ' !', 'id_user' => $user['id_user'], 'id_role' => $user['id_role'], 'nom' => $user['nom'], 'prenom' => $user['prenom'], 'redirect' => '../../vue/frontoffice/medecins.html']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'loginOAuth':
        try {
            $db       = config::getConnexion();
            $provider = trim($_POST['provider'] ?? '');
            $oauthId  = trim($_POST['oauth_id']  ?? '');
            $email    = trim($_POST['email']     ?? '');
            $nom      = trim($_POST['nom']       ?? '');
            $prenom   = trim($_POST['prenom']    ?? '');

            if (!$provider || !$email) { echo json_encode(['success' => false, 'error' => 'Données OAuth manquantes.']); break; }

            $q = $db->prepare("SELECT * FROM user WHERE email = :email");
            $q->execute(['email' => $email]);
            $user = $q->fetch();

            if ($user) {
                if ($user['statut_cmpt'] === 'bloqué' || $user['statut_cmpt'] === 'bloque') {
                    echo json_encode(['success' => false, 'error' => 'Votre compte a été suspendu. Contactez le support.', 'banned' => true]);
                    break;
                }
                $upd = $db->prepare("UPDATE user SET oauth_provider=:p, oauth_id=:oid, last_login=NOW() WHERE id_user=:id");
                $upd->execute(['p' => $provider, 'oid' => $oauthId, 'id' => $user['id_user']]);
            } else {
                $q2 = $db->prepare("SELECT id_user FROM user");
                $q2->execute();
                $ids = array_column($q2->fetchAll(), 'id_user');
                $newId = null;
                for ($i = 0; $i < 500; $i++) { $c = rand(1, 99999999); if (!in_array($c, $ids)) { $newId = $c; break; } }
                $ins = $db->prepare("INSERT INTO user (id_user,nom,prenom,email,mot_de_passe,statut_cmpt,cin,service,id_role,oauth_provider,oauth_id,last_login) VALUES (:id,:nom,:prenom,:email,:mdp,'actif','','',1,:p,:oid,NOW())");
                $ins->execute(['id' => $newId, 'nom' => $nom ?: explode('@',$email)[0], 'prenom' => $prenom ?: '', 'email' => $email, 'mdp' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'p' => $provider, 'oid' => $oauthId]);
                $q->execute(['email' => $email]);
                $user = $q->fetch();
            }

            $_SESSION['user_id']    = $user['id_user'];
            $_SESSION['user_role']  = $user['id_role'];
            $_SESSION['user_name']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_email'] = $user['email'];

            echo json_encode(['success' => true, 'message' => 'Connexion ' . ucfirst($provider) . ' réussie !', 'id_user' => $user['id_user'], 'id_role' => $user['id_role'], 'redirect' => '../../vue/frontoffice/medecins.html']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'loginFace':
        try {
            $db      = config::getConnexion();
            $userId  = intval($_POST['id_user'] ?? 0);
            $descriptor = $_POST['descriptor'] ?? '';

            if (!$userId || !$descriptor) { echo json_encode(['success' => false, 'error' => 'Données biométriques manquantes.']); break; }

            $q = $db->prepare("SELECT * FROM user WHERE id_user = :id");
            $q->execute(['id' => $userId]);
            $user = $q->fetch();

            if (!$user || !$user['face_descriptor']) { echo json_encode(['success' => false, 'error' => 'Aucun visage enregistré pour ce compte.']); break; }
            if ($user['statut_cmpt'] === 'bloqué' || $user['statut_cmpt'] === 'bloque') { echo json_encode(['success' => false, 'error' => 'Compte suspendu.', 'banned' => true]); break; }

            $stored = json_decode($user['face_descriptor'], true);
            $input  = json_decode($descriptor, true);

            if (!$stored || !$input || count($stored) !== count($input)) { echo json_encode(['success' => false, 'error' => 'Descripteur facial invalide.']); break; }

            $dist = 0;
            for ($i = 0; $i < count($stored); $i++) { $dist += pow($stored[$i] - $input[$i], 2); }
            $dist = sqrt($dist);

            if ($dist > 0.6) { echo json_encode(['success' => false, 'error' => 'Visage non reconnu. Veuillez réessayer.', 'distance' => $dist]); break; }

            $_SESSION['user_id']    = $user['id_user'];
            $_SESSION['user_role']  = $user['id_role'];
            $_SESSION['user_name']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_email'] = $user['email'];

            $upd = $db->prepare("UPDATE user SET last_login=NOW() WHERE id_user=:id");
            $upd->execute(['id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Reconnaissance faciale réussie !', 'id_user' => $user['id_user'], 'id_role' => $user['id_role'], 'redirect' => '../../vue/frontoffice/medecins.html']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'saveFaceDescriptor':
        try {
            $db         = config::getConnexion();
            $userId     = intval($_POST['id_user'] ?? 0);
            $descriptor = $_POST['descriptor'] ?? '';
            if (!$userId || !$descriptor) { echo json_encode(['success' => false, 'error' => 'Données manquantes.']); break; }
            $upd = $db->prepare("UPDATE user SET face_descriptor=:fd WHERE id_user=:id");
            $upd->execute(['fd' => $descriptor, 'id' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Visage enregistré avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => '../frontoffice/login.html']);
        break;

    case 'checkSession':
        if (isset($_SESSION['user_id'])) {
            echo json_encode(['success' => true, 'loggedIn' => true, 'id_user' => $_SESSION['user_id'], 'id_role' => $_SESSION['user_role'], 'name' => $_SESSION['user_name']]);
        } else {
            echo json_encode(['success' => true, 'loggedIn' => false]);
        }
        break;

    case 'forgotPassword':
        try {
            $db    = config::getConnexion();
            $email = trim($_POST['email'] ?? '');

            $q = $db->prepare("SELECT id_user, prenom FROM user WHERE email = :email");
            $q->execute(['email' => $email]);
            $user = $q->fetch();

            if (!$user) {
                echo json_encode(['success' => true, 'message' => 'Si cet email existe, un lien a été envoyé.']);
                break;
            }

            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $del = $db->prepare("DELETE FROM password_reset_tokens WHERE id_user = :id_user");
            $del->execute(['id_user' => $user['id_user']]);

            $ins = $db->prepare("INSERT INTO password_reset_tokens (id_user, token, expires_at, used) VALUES (:id_user, :token, :expires_at, 0)");
            $ins->execute(['id_user' => $user['id_user'], 'token' => $token, 'expires_at' => $expiresAt]);

            $prenom    = htmlspecialchars($user['prenom']);
            $resetLink = "http://localhost/gestion_users/vue/frontoffice/reset_password.html?token=$token";
            $subject   = 'Réinitialisation de votre mot de passe JumeauNum';
            $body = "<div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;background:#f8faff;padding:32px;border-radius:16px'>
              <div style='background:linear-gradient(135deg,#1e3a8a,#2563eb);border-radius:12px;padding:28px;text-align:center;margin-bottom:24px'>
                <h1 style='color:white;font-size:1.6rem;margin:0'>JumeauNum</h1>
                <p style='color:rgba(255,255,255,.8);margin:8px 0 0'>Réinitialisation du mot de passe</p>
              </div>
              <h2 style='color:#0f172a;font-size:1.2rem'>Bonjour $prenom,</h2>
              <p style='color:#475569;line-height:1.7'>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous. Ce lien est valable <strong>30 minutes</strong>.</p>
              <a href='$resetLink' style='display:block;background:#2563eb;color:white;text-align:center;padding:16px;border-radius:8px;text-decoration:none;font-weight:600;margin:24px 0;font-size:1rem'>Réinitialiser mon mot de passe</a>
              <p style='color:#94a3b8;font-size:.8rem;'>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            </div>";

            sendEmail($email, $subject, $body);
            echo json_encode(['success' => true, 'message' => 'Un lien de réinitialisation a été envoyé à votre email.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'verifyResetToken':
        try {
            $db    = config::getConnexion();
            $token = trim($_GET['token'] ?? '');
            $q = $db->prepare("SELECT * FROM password_reset_tokens WHERE token=:t AND used=0 AND expires_at > NOW()");
            $q->execute(['t' => $token]);
            $row = $q->fetch();
            if ($row) {
                echo json_encode(['success' => true, 'id_user' => $row['id_user']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Token invalide ou expiré.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'resetPassword':
        try {
            $db      = config::getConnexion();
            $token   = $_POST['token'] ?? '';
            $nouveau = $_POST['nouveau_mdp'] ?? '';

            $q = $db->prepare("SELECT * FROM password_reset_tokens WHERE token=:t AND used=0 AND expires_at > NOW()");
            $q->execute(['t' => $token]);
            $row = $q->fetch();

            if (!$row) { echo json_encode(['success' => false, 'error' => 'Lien invalide ou expiré.']); break; }

            $upd = $db->prepare("UPDATE user SET mot_de_passe=:mdp WHERE id_user=:id");
            $upd->execute(['mdp' => password_hash($nouveau, PASSWORD_BCRYPT), 'id' => $row['id_user']]);

            $del = $db->prepare("UPDATE password_reset_tokens SET used=1 WHERE token=:t");
            $del->execute(['t' => $token]);

            echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'changeStatutWithAlert':
        try {
            $db      = config::getConnexion();
            $idUser  = intval($_POST['id_user'] ?? 0);
            $statut  = $_POST['statut'] ?? '';
            $raison  = trim($_POST['raison'] ?? 'Aucune raison spécifiée.');
            $notifEmail = ($_POST['notify_email'] ?? '0') === '1';
            $notifSMS   = ($_POST['notify_sms']   ?? '0') === '1';

            $upd = $db->prepare("UPDATE user SET statut_cmpt=:s WHERE id_user=:id");
            $upd->execute(['s' => $statut, 'id' => $idUser]);

            $q = $db->prepare("SELECT nom, prenom, email FROM user WHERE id_user=:id");
            $q->execute(['id' => $idUser]);
            $user = $q->fetch();

            if ($user) {
                if ($statut === 'bloqué' || $statut === 'bloque') {
                    $subject = 'JumeauNum - Votre compte a été suspendu';
                    $body    = "<h2>Compte suspendu</h2><p>Bonjour {$user['prenom']} {$user['nom']},</p><p>Votre compte a été <strong>suspendu</strong>.</p><p><strong>Raison :</strong> $raison</p>";
                    $smsMsg  = "JumeauNum: Votre compte a été suspendu. Raison: $raison.";
                } else {
                    $subject = 'JumeauNum - Votre compte a été réactivé';
                    $body    = "<h2>Compte réactivé</h2><p>Bonjour {$user['prenom']} {$user['nom']},</p><p>Votre compte a été <strong>réactivé</strong>.</p>";
                    $smsMsg  = "JumeauNum: Votre compte a été réactivé.";
                }

                if ($notifEmail) sendEmail($user['email'], $subject, $body);
                if ($notifSMS)   sendAlertSMS('+21600000000', $smsMsg);

                $log = $db->prepare("INSERT INTO user_action_log (id_user, action, detail, done_by, done_at) VALUES (:id, :act, :det, :by, NOW())");
                $log->execute(['id' => $idUser, 'act' => ($statut === 'bloqué' || $statut === 'bloque') ? 'BAN' : 'UNBAN', 'det' => $raison, 'by' => $_SESSION['user_id'] ?? 0]);
            }

            echo json_encode(['success' => true, 'message' => 'Statut mis à jour et notification envoyée.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getLoginAttempts':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT email, ip_address, COUNT(*) as total, SUM(success=0) as failed, MAX(attempt_time) as last_attempt FROM login_attempts WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY email, ip_address HAVING failed >= 3 ORDER BY failed DESC LIMIT 50");
            $q->execute();
            echo json_encode(['success' => true, 'data' => $q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'ping':
        try {
            $db = config::getConnexion();
            echo json_encode(['success' => true, 'message' => 'Connexion OK']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

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

    case 'checkCin':
        try {
            $db = config::getConnexion();
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

    case 'signup':
        try {
            $db = config::getConnexion();
            $nom     = trim($_POST['nom']    ?? '');
            $prenom  = trim($_POST['prenom'] ?? '');
            $email   = trim($_POST['email']  ?? '');
            $mdp     = $_POST['mot_de_passe'] ?? '';
            $cin     = trim($_POST['cin']    ?? '');
            $service = trim($_POST['service'] ?? '');

            $q = $db->prepare("SELECT id_user FROM user");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_user');
            $newId = null;
            for ($i = 0; $i < 500; $i++) { $c = rand(1, 99999999); if (!in_array($c, $ids)) { $newId = $c; break; } }
            if (!$newId) { echo json_encode(['success'=>false,'error'=>'Impossible de générer un ID.']); break; }

            $chkEmail = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE email = :email");
            $chkEmail->execute(['email' => $email]);
            if ((int)$chkEmail->fetch()['cnt'] > 0) { echo json_encode(['success'=>false,'error'=>'Cet email est déjà utilisé.']); break; }

            $chkCin = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin = :cin");
            $chkCin->execute(['cin' => $cin]);
            if ((int)$chkCin->fetch()['cnt'] > 0) { echo json_encode(['success'=>false,'error'=>'Ce CIN est déjà utilisé.']); break; }

            $user = new User($newId, $nom, $prenom, $email, password_hash($mdp, PASSWORD_BCRYPT), 'actif', $cin, $service, 1);
            $sql  = "INSERT INTO user (id_user,nom,prenom,email,mot_de_passe,statut_cmpt,cin,service,id_role) VALUES (:id_user,:nom,:prenom,:email,:mot_de_passe,:statut_cmpt,:cin,:service,:id_role)";
            $ins  = $db->prepare($sql);
            $ins->execute(['id_user' => $user->getIdUser(), 'nom' => $user->getNom(), 'prenom' => $user->getPrenom(), 'email' => $user->getEmail(), 'mot_de_passe' => $user->getMdp(), 'statut_cmpt' => $user->getStatut(), 'cin' => $user->getCin(), 'service' => $user->getService(), 'id_role' => $user->getIdRole()]);

            $_SESSION['user_id']    = $newId;
            $_SESSION['user_role']  = 1;
            $_SESSION['user_name']  = $prenom . ' ' . $nom;
            $_SESSION['user_email'] = $email;

            $prenomEsc = htmlspecialchars($prenom);
            $emailEsc  = htmlspecialchars($email);
            $subject = 'Bienvenue sur JumeauNum !';
            $body = "<div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;background:#f8faff;padding:32px;border-radius:16px'>
              <div style='background:linear-gradient(135deg,#1e3a8a,#2563eb);border-radius:12px;padding:28px;text-align:center;margin-bottom:24px'>
                <h1 style='color:white;font-size:1.6rem;margin:0'>JumeauNum</h1>
                <p style='color:rgba(255,255,255,.8);margin:8px 0 0'>Plateforme de santé numérique</p>
              </div>
              <h2 style='color:#0f172a;font-size:1.2rem'>Bonjour $prenomEsc,</h2>
              <p style='color:#475569;line-height:1.7'>Votre compte a été créé avec succès sur <strong>JumeauNum</strong>.</p>
              <div style='background:#dbeafe;border-radius:10px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#1e40af;font-size:.9rem'>Email : $emailEsc<br>Rôle : Patient</p>
              </div>
              <a href='http://localhost/gestion_users/vue/frontoffice/medecins.html' style='display:block;background:#2563eb;color:white;text-align:center;padding:14px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:20px'>Accéder à la plateforme</a>
            </div>";
            sendEmail($email, $subject, $body);

            echo json_encode(['success' => true, 'message' => 'Compte créé avec succès ! Bienvenue sur JumeauNum.', 'id_user' => $newId, 'redirect' => '../../vue/frontoffice/medecins.html']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getProfil':
        try {
            $db = config::getConnexion();
            $userId = intval($_GET['id_user'] ?? $_SESSION['user_id'] ?? 0);
            $q  = $db->prepare("SELECT id_user,nom,prenom,email,statut_cmpt,cin,service,id_role FROM user WHERE id_user=:id");
            $q->execute(['id' => $userId]);
            $user = $q->fetch();
            if ($user) echo json_encode(['success'=>true,'data'=>$user]);
            else echo json_encode(['success'=>false,'error'=>'Utilisateur introuvable.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'updateProfil':
        try {
            $db     = config::getConnexion();
            $idUser = intval($_POST['id_user'] ?? $_SESSION['user_id'] ?? 0);

            $chkEmail = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE email=:email AND id_user!=:id");
            $chkEmail->execute(['email' => $_POST['email']??'', 'id' => $idUser]);
            if ((int)$chkEmail->fetch()['cnt'] > 0) { echo json_encode(['success'=>false,'error'=>'Email déjà utilisé.']); break; }

            $chkCin = $db->prepare("SELECT COUNT(*) as cnt FROM user WHERE cin=:cin AND id_user!=:id");
            $chkCin->execute(['cin' => $_POST['cin']??'', 'id' => $idUser]);
            if ((int)$chkCin->fetch()['cnt'] > 0) { echo json_encode(['success'=>false,'error'=>'CIN déjà utilisé.']); break; }

            $upd = $db->prepare("UPDATE user SET nom=:nom,prenom=:prenom,email=:email,cin=:cin,service=:service WHERE id_user=:id");
            $upd->execute(['nom' => trim($_POST['nom']??''), 'prenom' => trim($_POST['prenom']??''), 'email' => trim($_POST['email']??''), 'cin' => trim($_POST['cin']??''), 'service' => trim($_POST['service']??''), 'id' => $idUser]);

            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $idUser) {
                $_SESSION['user_name']  = trim($_POST['prenom']??'') . ' ' . trim($_POST['nom']??'');
                $_SESSION['user_email'] = trim($_POST['email']??'');
            }
            echo json_encode(['success'=>true,'message'=>'Profil mis à jour avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'changeMdp':
        try {
            $db     = config::getConnexion();
            $idUser = intval($_POST['id_user'] ?? $_SESSION['user_id'] ?? 0);
            $ancien  = $_POST['ancien_mdp']  ?? '';
            $nouveau = $_POST['nouveau_mdp'] ?? '';

            $q = $db->prepare("SELECT mot_de_passe FROM user WHERE id_user=:id");
            $q->execute(['id' => $idUser]);
            $row = $q->fetch();

            if (!$row) { echo json_encode(['success'=>false,'error'=>'Utilisateur introuvable.']); break; }
            if (!password_verify($ancien, $row['mot_de_passe'])) { echo json_encode(['success'=>false,'error'=>'Ancien mot de passe incorrect.']); break; }
            if (password_verify($nouveau, $row['mot_de_passe'])) { echo json_encode(['success'=>false,'error'=>'Le nouveau mot de passe doit être différent.']); break; }

            $upd = $db->prepare("UPDATE user SET mot_de_passe=:mdp WHERE id_user=:id");
            $upd->execute(['mdp' => password_hash($nouveau, PASSWORD_BCRYPT), 'id' => $idUser]);
            echo json_encode(['success'=>true,'message'=>'Mot de passe mis à jour avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getAllUsers':
        try {
            $db   = config::getConnexion();
            $role = $_GET['role'] ?? '';
            if ($role !== '') {
                $q = $db->prepare("SELECT id_user,nom,prenom,email,cin,service,statut_cmpt,id_role FROM user WHERE id_role=:role ORDER BY nom");
                $q->execute(['role' => intval($role)]);
            } else {
                $q = $db->prepare("SELECT id_user,nom,prenom,email,cin,service,statut_cmpt,id_role FROM user ORDER BY nom");
                $q->execute();
            }
            echo json_encode(['success'=>true,'data'=>$q->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'changeRole':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("UPDATE user SET id_role=:role WHERE id_user=:id");
            $q->execute(['role' => intval($_POST['id_role']), 'id' => intval($_POST['id_user'])]);
            echo json_encode(['success'=>true,'message'=>'Rôle mis à jour.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'changeStatut':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("UPDATE user SET statut_cmpt=:statut WHERE id_user=:id");
            $q->execute(['statut' => $_POST['statut'], 'id' => intval($_POST['id_user'])]);
            echo json_encode(['success'=>true,'message'=>'Statut mis à jour.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'deleteUser':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("DELETE FROM user WHERE id_user=:id");
            $q->execute(['id' => intval($_POST['id_user'])]);
            echo json_encode(['success'=>true,'message'=>'Utilisateur supprimé.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getStats':
        try {
            $db = config::getConnexion();
            $q  = $db->query("SELECT id_role, COUNT(*) as total FROM user GROUP BY id_role");
            $rows = $q->fetchAll();
            $stats = ['patients'=>0,'medecins'=>0,'admins'=>0,'total'=>0];
            foreach ($rows as $r) {
                $stats['total'] += (int)$r['total'];
                if ($r['id_role']==1) $stats['patients'] = (int)$r['total'];
                if ($r['id_role']==3) $stats['medecins'] = (int)$r['total'];
                if ($r['id_role']==2) $stats['admins']   = (int)$r['total'];
            }
            echo json_encode(['success'=>true,'data'=>$stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'genId':
        try {
            $db  = config::getConnexion();
            $q   = $db->prepare("SELECT id_user FROM user");
            $q->execute();
            $ids = array_column($q->fetchAll(), 'id_user');
            $newId = null;
            for ($i = 0; $i < 500; $i++) { $c = rand(1, 99999999); if (!in_array($c, $ids)) { $newId = $c; break; } }
            echo json_encode(['success'=>true,'id'=>$newId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'getUserIdByEmail':
        try {
            $db = config::getConnexion();
            $q  = $db->prepare("SELECT id_user, face_descriptor FROM user WHERE email = :email");
            $q->execute(['email' => $_GET['email'] ?? '']);
            $row = $q->fetch();
            if ($row) {
                echo json_encode(['success' => true, 'id_user' => $row['id_user'], 'has_face' => !empty($row['face_descriptor'])]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Aucun compte trouvé pour cet email.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Action inconnue : "' . $action . '"']);
        break;

} // Fin du switch
?>
