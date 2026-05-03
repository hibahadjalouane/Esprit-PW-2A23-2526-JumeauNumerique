<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../modele/config.php';

// ── PHPMailer (à installer via Composer ou manuellement) ──────────────────────
// Chemin vers PHPMailer — adapte si nécessaire
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../../libs/PHPMailer/src/Exception.php';
require_once '../../libs/PHPMailer/src/PHPMailer.php';
require_once '../../libs/PHPMailer/src/SMTP.php';

// ── CONFIG EMAIL ──────────────────────────────────────────────────────────────
define('MAIL_FROM',     'jumeaunum@gmail.com');
define('MAIL_FROM_NAME','JumeauNum – Rappel Médical');
define('MAIL_PASSWORD', 'untx kpmg ajnh jmlh'); // App Password Gmail

$action = $_POST['action'] ?? '';

switch ($action) {

    // ── ENVOYER RAPPEL ────────────────────────────────────────────────────────
    case 'sendReminder':
        try {
            $db = config::getConnexion();

            $id_consultation = intval($_POST['id_consultation'] ?? 0);
            if (!$id_consultation) {
                echo json_encode(['success' => false, 'error' => 'ID consultation manquant.']);
                break;
            }

            // 1. Récupérer la consultation
            $q = $db->prepare("
                SELECT c.id_consultation, c.date_consultation, c.motif, c.diagnostic, c.notes,
                       c.id_dossier
                FROM consultation c
                WHERE c.id_consultation = :id
            ");
            $q->execute(['id' => $id_consultation]);
            $consultation = $q->fetch();

            if (!$consultation) {
                echo json_encode(['success' => false, 'error' => 'Consultation introuvable.']);
                break;
            }

            // 2. Récupérer le patient via le dossier
            $q2 = $db->prepare("
                SELECT u.Email, u.Nom, u.Prenom
                FROM dossier_medical d
                JOIN user u ON d.id_patient = u.id_user
                WHERE d.id_dossier = :id_dossier
            ");
            $q2->execute(['id_dossier' => $consultation['id_dossier']]);
            $patient = $q2->fetch();

            if (!$patient || empty($patient['Email'])) {
                echo json_encode(['success' => false, 'error' => 'Email du patient introuvable.']);
                break;
            }

            // 3. Récupérer le médecin via le dossier
            $q3 = $db->prepare("
                SELECT u.Nom, u.Prenom
                FROM dossier_medical d
                JOIN user u ON d.id_medecin = u.id_user
                WHERE d.id_dossier = :id_dossier
            ");
            $q3->execute(['id_dossier' => $consultation['id_dossier']]);
            $medecin = $q3->fetch();
            $medecinNom = $medecin ? 'Dr. ' . $medecin['Nom'] . ' ' . $medecin['Prenom'] : 'votre médecin';

            // 4. Formater la date
            $date = $consultation['date_consultation']
                ? date('d/m/Y', strtotime($consultation['date_consultation']))
                : '—';

            $patientNom  = trim($patient['Nom'] . ' ' . $patient['Prenom']);
            $patientEmail = $patient['Email'];

            // 5. Envoyer l'email
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_FROM;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($patientEmail, $patientNom);
            $mail->Subject = "Rappel de consultation – JumeauNum";

            // Corps HTML de l'email
            $mail->isHTML(true);
            $mail->Body = "
<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'/></head>
<body style='font-family:Arial,sans-serif;background:#f5f7ff;margin:0;padding:0;'>
  <div style='max-width:560px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>

    <!-- Header -->
    <div style='background:#1a4fcc;padding:32px 40px;'>
      <h1 style='color:#fff;margin:0;font-size:22px;letter-spacing:-0.5px;'>JumeauNum</h1>
      <p style='color:rgba(255,255,255,0.75);margin:6px 0 0;font-size:13px;'>Système de gestion médicale</p>
    </div>

    <!-- Body -->
    <div style='padding:36px 40px;'>
      <p style='color:#0d1526;font-size:16px;font-weight:600;margin:0 0 8px;'>Bonjour {$patientNom},</p>
      <p style='color:#5c6785;font-size:14px;line-height:1.7;margin:0 0 28px;'>
        Nous vous rappelons qu'une consultation est enregistrée dans votre dossier médical.
        Voici le récapitulatif :
      </p>

      <!-- Consultation details -->
      <div style='background:#f5f7ff;border-radius:10px;padding:24px;margin-bottom:28px;border-left:4px solid #1a4fcc;'>
        <table style='width:100%;border-collapse:collapse;font-size:14px;'>
          <tr>
            <td style='padding:6px 0;color:#9aa3c0;font-weight:600;width:140px;'>Date</td>
            <td style='padding:6px 0;color:#0d1526;font-weight:600;'>{$date}</td>
          </tr>
          <tr>
            <td style='padding:6px 0;color:#9aa3c0;font-weight:600;'>Motif</td>
            <td style='padding:6px 0;color:#0d1526;'>{$consultation['motif']}</td>
          </tr>
          <tr>
            <td style='padding:6px 0;color:#9aa3c0;font-weight:600;'>Médecin</td>
            <td style='padding:6px 0;color:#0d1526;'>{$medecinNom}</td>
          </tr>
          " . ($consultation['diagnostic'] ? "
          <tr>
            <td style='padding:6px 0;color:#9aa3c0;font-weight:600;'>Diagnostic</td>
            <td style='padding:6px 0;color:#0d1526;'>{$consultation['diagnostic']}</td>
          </tr>" : "") . "
          " . ($consultation['notes'] ? "
          <tr>
            <td style='padding:6px 0;color:#9aa3c0;font-weight:600;'>Notes</td>
            <td style='padding:6px 0;color:#0d1526;'>{$consultation['notes']}</td>
          </tr>" : "") . "
        </table>
      </div>

      <p style='color:#5c6785;font-size:13px;line-height:1.7;margin:0;'>
        Si vous avez des questions, n'hésitez pas à contacter votre médecin référent.
      </p>
    </div>

    <!-- Footer -->
    <div style='background:#f5f7ff;padding:20px 40px;border-top:1px solid #e2e8f5;'>
      <p style='color:#9aa3c0;font-size:12px;margin:0;text-align:center;'>
        Cet email a été envoyé automatiquement par JumeauNum. Ne pas répondre à cet email.
      </p>
    </div>
  </div>
</body>
</html>";

            // Version texte
            $mail->AltBody = "Bonjour {$patientNom},\n\nRappel de consultation :\nDate : {$date}\nMotif : {$consultation['motif']}\nMédecin : {$medecinNom}\n\nJumeauNum";

            $mail->send();

            echo json_encode([
                'success' => true,
                'message' => "Rappel envoyé avec succès à {$patientEmail} !",
                'email'   => $patientEmail
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur envoi email : ' . $e->getMessage()]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur : ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
        break;

    // ── ENVOYER RÉFÉRENCEMENT ─────────────────────────────────────────────────
    case 'sendReferral':
        try {
            $db = config::getConnexion();

            $id_consultation = intval($_POST['id_consultation'] ?? 0);
            $city            = trim($_POST['city'] ?? '');
            $places          = json_decode($_POST['places'] ?? '[]', true);

            if (!$id_consultation || !$city || empty($places)) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes.']);
                break;
            }

            // Récupérer patient via dossier
            $q = $db->prepare("
                SELECT u.Email, u.Nom, u.Prenom, c.motif, c.diagnostic, c.date_consultation
                FROM consultation c
                JOIN dossier_medical d ON c.id_dossier = d.id_dossier
                JOIN user u ON d.id_patient = u.id_user
                WHERE c.id_consultation = :id
            ");
            $q->execute(['id' => $id_consultation]);
            $row = $q->fetch();

            if (!$row || empty($row['Email'])) {
                echo json_encode(['success' => false, 'error' => 'Email du patient introuvable.']);
                break;
            }

            $patientNom   = trim($row['Nom'] . ' ' . $row['Prenom']);
            $patientEmail = $row['Email'];
            $date         = $row['date_consultation']
                ? date('d/m/Y', strtotime($row['date_consultation']))
                : '—';

            // Construire les lignes de lieux
            $placesHtml = '';
            foreach ($places as $p) {
                $mapsUrl     = 'https://www.google.com/maps/search/' . urlencode($p['maps'] . ' ' . $city);
                $placesHtml .= "
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #e2e8f5;'>
                    <span style='font-size:1.1rem;'>{$p['emoji']}</span>
                    <strong style='color:#0d1526;font-size:.9rem;margin-left:8px;'>{$p['label']}</strong>
                  </td>
                  <td style='padding:10px 0;border-bottom:1px solid #e2e8f5;text-align:right;'>
                    <a href='{$mapsUrl}' style='background:#1a4fcc;color:#fff;text-decoration:none;padding:6px 14px;border-radius:6px;font-size:.78rem;font-weight:700;'>
                      Voir sur Maps
                    </a>
                  </td>
                </tr>";
            }

            // Envoyer email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_FROM;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($patientEmail, $patientNom);
            $mail->Subject = "Établissements recommandés – JumeauNum";
            $mail->isHTML(true);

            $mail->Body = "
<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'/></head>
<body style='font-family:Arial,sans-serif;background:#f5f7ff;margin:0;padding:0;'>
  <div style='max-width:560px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
    <div style='background:#1a4fcc;padding:32px 40px;'>
      <h1 style='color:#fff;margin:0;font-size:22px;letter-spacing:-0.5px;'>JumeauNum</h1>
      <p style='color:rgba(255,255,255,0.75);margin:6px 0 0;font-size:13px;'>Système de gestion médicale</p>
    </div>
    <div style='padding:36px 40px;'>
      <p style='color:#0d1526;font-size:16px;font-weight:600;margin:0 0 8px;'>Bonjour {$patientNom},</p>
      <p style='color:#5c6785;font-size:14px;line-height:1.7;margin:0 0 6px;'>
        Suite à votre consultation du <strong>{$date}</strong>, votre médecin vous recommande de vous rendre dans les établissements suivants à <strong>{$city}</strong> :
      </p>
      <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
        {$placesHtml}
      </table>
      <p style='color:#5c6785;font-size:13px;line-height:1.7;margin:16px 0 0;'>
        Cliquez sur <strong>\"Voir sur Maps\"</strong> pour trouver l'établissement le plus proche de chez vous.
      </p>
    </div>
    <div style='background:#f5f7ff;padding:20px 40px;border-top:1px solid #e2e8f5;'>
      <p style='color:#9aa3c0;font-size:12px;margin:0;text-align:center;'>
        Cet email a été envoyé automatiquement par JumeauNum. Ne pas répondre à cet email.
      </p>
    </div>
  </div>
</body>
</html>";

            $mail->AltBody = "Bonjour {$patientNom},\n\nVotre médecin vous recommande les établissements suivants à {$city} :\n\n"
                . implode("\n", array_map(fn($p) => "- {$p['emoji']} {$p['label']}: https://www.google.com/maps/search/" . urlencode($p['maps'] . ' ' . $city), $places));

            $mail->send();

            echo json_encode([
                'success' => true,
                'message' => "Référencement envoyé à {$patientEmail} !"
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur envoi : ' . $e->getMessage()]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur : ' . $e->getMessage()]);
        }
        break;
}
?>