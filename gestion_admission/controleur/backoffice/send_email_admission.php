<?php
/**
 * send_email_admission.php
 * Chemin : gestion_admission/controleur/backoffice/send_email_admission.php
 *
 * Envoie un email HTML de confirmation après la création d'une admission.
 * Utilise PHPMailer avec SMTP Gmail.
 *
 * AVANT D'UTILISER :
 * 1. Avoir installé PHPMailer : composer require phpmailer/phpmailer
 *    (le dossier vendor/ est à la racine du projet)
 * 2. Remplacer GMAIL_APP_PASSWORD par le mot de passe d'application Gmail
 *    (Compte Google > Sécurité > Mots de passe d'application)
 */

// Chemin vers le vendor depuis gestion_admission/controleur/backoffice/
// On remonte 3 niveaux pour atteindre la racine du projet
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email de confirmation d'admission au patient.
 *
 * @param array $data  Données à insérer dans l'email :
 *   - email_destinataire : string  (email du patient)
 *   - nom_patient        : string  (nom + prénom)
 *   - id_admission       : int
 *   - date_arrive        : string  (YYYY-MM-DD)
 *   - salle_numero       : int|null
 *   - id_ticket          : int|null
 *
 * @return array  ['success' => bool, 'error' => string|null]
 */
function envoyerEmailAdmission(array $data): array
{
    // Données de connexion SMTP : remplacer les valeurs ci-dessous
    $expediteurEmail = '';  //lena email
    $expediteurNom   = 'JumeauNum';
    $appPassword     = '';   //lena app password

    // Formater la date pour l'affichage (JJ/MM/AAAA à HH:MM)
    $dateFormatee = date('d/m/Y à H:i', strtotime($data['date_arrive']));

    // Numéro de salle : afficher le numéro si disponible, sinon "Non assignée"
    $salleAffichage = $data['salle_numero'] !== null
        ? 'Salle n°' . htmlspecialchars($data['salle_numero'])
        : 'Non assignée';

    // Ticket : afficher l'ID si disponible
    $ticketAffichage = $data['id_ticket'] !== null
        ? '#' . htmlspecialchars($data['id_ticket'])
        : 'Aucun ticket associé';

    // Corps du message en version texte simple (anti-spam)
    $textePlain = "Bonjour " . $data['nom_patient'] . ",\n\n"
        . "Nous vous confirmons votre admission dans notre établissement.\n\n"
        . "Détails de l'admission :\n"
        . "- Numéro d'admission : #" . $data['id_admission'] . "\n"
        . "- Date d'arrivée : " . $dateFormatee . "\n"
        . "- Salle assignée : " . strip_tags($salleAffichage) . "\n"
        . "- Ticket utilisé : " . $ticketAffichage . " (ce ticket est maintenant invalide)\n\n"
        . "Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter.\n\n"
        . "Merci pour votre confiance.\n"
        . "Cordialement,\n"
        . "L'équipe JumeauNum";

    // Corps du message en HTML
    $corpsHtml = '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Confirmation d\'admission - JumeauNum</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4fb;font-family:\'Segoe UI\',Arial,sans-serif;">

  <!-- Conteneur principal -->
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4fb;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;">

          <!-- En-tête avec logo -->
          <tr>
            <td style="background:#2563eb;padding:28px 32px;text-align:center;">
              <div style="display:inline-flex;align-items:center;gap:10px;">
                <!-- Icône ECG simple en table (compatible email) -->
                <table cellpadding="0" cellspacing="0" style="display:inline-block;">
                  <tr>
                    <td style="background:#fff;border-radius:8px;width:44px;height:44px;text-align:center;vertical-align:middle;">
                      <span style="color:#2563eb;font-size:22px;font-weight:bold;">♥</span>
                    </td>
                  </tr>
                </table>
                <span style="color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">JumeauNum</span>
              </div>
              <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:13px;">Système de Gestion Hospitalière</p>
            </td>
          </tr>

          <!-- Bandeau de confirmation -->
          <tr>
            <td style="background:#eff4ff;border-bottom:2px solid #dde2ef;padding:16px 32px;text-align:center;">
              <span style="color:#2563eb;font-size:15px;font-weight:600;">
                Confirmation d\'admission
              </span>
            </td>
          </tr>

          <!-- Corps du message -->
          <tr>
            <td style="padding:32px;">

              <p style="margin:0 0 18px;font-size:15px;color:#111827;">
                Bonjour <strong>' . htmlspecialchars($data['nom_patient']) . '</strong>,
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#4b5563;line-height:1.6;">
                Nous vous confirmons votre enregistrement dans notre établissement hospitalier.<br/>
                Veuillez trouver ci-dessous les détails de votre admission.
              </p>

              <!-- Carte de détails -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1px solid #dde2ef;border-radius:10px;overflow:hidden;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;border-bottom:1px solid #dde2ef;background:#eff4ff;">
                    <span style="font-size:13px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:0.05em;">
                      Détails de l\'admission
                    </span>
                  </td>
                </tr>

                <!-- Numéro d\'admission -->
                <tr>
                  <td style="padding:13px 20px;border-bottom:1px solid #eef1f8;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;width:50%;">Numéro d\'admission</td>
                        <td style="font-size:14px;font-weight:700;color:#111827;text-align:right;">
                          #' . htmlspecialchars($data['id_admission']) . '
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Date d\'arrivée -->
                <tr>
                  <td style="padding:13px 20px;border-bottom:1px solid #eef1f8;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;width:50%;">Date d\'arrivée</td>
                        <td style="font-size:14px;font-weight:600;color:#111827;text-align:right;">
                          ' . $dateFormatee . '
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Salle assignée -->
                <tr>
                  <td style="padding:13px 20px;border-bottom:1px solid #eef1f8;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;width:50%;">Salle assignée</td>
                        <td style="font-size:14px;font-weight:600;color:#111827;text-align:right;">
                          ' . $salleAffichage . '
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Ticket utilisé -->
                <tr>
                  <td style="padding:13px 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;width:50%;">Ticket utilisé</td>
                        <td style="text-align:right;">
                          <span style="display:inline-block;background:#fef3c7;color:#d97706;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;">
                            ' . $ticketAffichage . '
                          </span>
                          <br/>
                          <span style="font-size:11px;color:#9ca3af;">Ce ticket est maintenant invalide</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

              </table>

              <!-- Message d\'information -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0;font-size:13px;color:#16a34a;">
                      Votre dossier est en cours de traitement par notre équipe médicale.
                      Un médecin vous prendra en charge dans les meilleurs délais.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 6px;font-size:13px;color:#6b7280;line-height:1.6;">
                Si vous avez des questions ou besoin d\'assistance, n\'hésitez pas à nous contacter.
              </p>
              <p style="margin:0;font-size:13px;color:#6b7280;">
                Merci pour votre confiance.
              </p>

            </td>
          </tr>

          <!-- Pied de page -->
          <tr>
            <td style="background:#f0f4fb;border-top:1px solid #dde2ef;padding:20px 32px;text-align:center;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#2563eb;">JumeauNum</p>
              <p style="margin:0;font-size:12px;color:#9ca3af;">Cet email est envoyé automatiquement, merci de ne pas y répondre.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>';

    // Configuration de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Paramètres SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $expediteurEmail;
        $mail->Password   = $appPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Expéditeur et destinataire
        $mail->setFrom($expediteurEmail, $expediteurNom);
        $mail->addAddress($data['email_destinataire'], $data['nom_patient']);

        // Objet et corps
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation d\'admission #' . $data['id_admission'] . ' - JumeauNum';
        $mail->Body    = $corpsHtml;
        $mail->AltBody = $textePlain;

        // Entêtes pour éviter le spam
        $mail->addCustomHeader('X-Mailer', 'JumeauNum-Mailer');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();

        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        // Afficher l'erreur visible côté serveur pour le débogage
        var_dump('Erreur PHPMailer : ' . $mail->ErrorInfo);

        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}
?>
