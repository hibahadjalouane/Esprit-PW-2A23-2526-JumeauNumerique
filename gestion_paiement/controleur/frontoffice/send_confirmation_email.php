<?php
/**
 * send_confirmation_email.php
 * Chemin : gestion_paiement/controleur/frontoffice/send_confirmation_email.php
 *
 * Fonction qui envoie un email de confirmation de paiement via PHPMailer + Gmail SMTP.
 * Ce fichier est inclus par payer_facture.php, il n'est pas appelé directement.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie l'email de confirmation après paiement.
 *
 * @param array $destinataire  ['email' => '...', 'prenom' => '...', 'nom' => '...']
 * @param array $facture       ['id_facture' => ..., 'montant' => ..., 'nom_type' => ...]
 * @param array $ticket        ['id_ticket' => ..., 'date_creation' => ..., 'date_expiration' => ...]
 * @return array               ['success' => bool, 'message' => string]
 */
function envoyerEmailConfirmation(array $destinataire, array $facture, array $ticket): array
{
    // Charge Composer (PHPMailer)
    require_once __DIR__ . '/../../../vendor/autoload.php';

    // Identifiants Gmail — utilise les variables d'environnement si disponibles
    // Sinon remplace les valeurs par défaut par les tiennes
    $gmailUser = getenv('GMAIL_USER') ?: 'yakinhidourii@gmail.com';
    $gmailPass = getenv('GMAIL_PASS') ?: 'eqfv dxmm ysvz grtw';

    // Formatage des dates pour l'affichage dans l'email
    $dateCreation   = date('d/m/Y à H:i:s', strtotime($ticket['date_creation']));
    $dateExpiration = date('d/m/Y à H:i:s', strtotime($ticket['date_expiration']));
    $montantFormate = number_format((float) $facture['montant'], 2, ',', ' ') . ' DT';
    $nomComplet     = htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']);
    $idFacture      = str_pad($facture['id_facture'], 4, '0', STR_PAD_LEFT);
    $idTicket       = $ticket['id_ticket'];

    // Version HTML de l'email
    $htmlBody = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
      <meta charset='UTF-8'>
      <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      <title>Confirmation de paiement - JumeauNum</title>
    </head>
    <body style='margin:0;padding:0;background-color:#f4f6fb;font-family:Arial,Helvetica,sans-serif;'>
      <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6fb;padding:40px 0;'>
        <tr>
          <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

              <!-- En-tête -->
              <tr>
                <td style='background:linear-gradient(135deg,#2563EB 0%,#14B8A6 100%);padding:36px 40px;text-align:center;'>
                  <h1 style='color:#ffffff;margin:0;font-size:26px;font-weight:700;letter-spacing:-0.5px;'>JumeauNum</h1>
                  <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;'>Plateforme hospitalière numérique</p>
                </td>
              </tr>

              <!-- Icône succès -->
              <tr>
                <td align='center' style='padding:36px 40px 0;'>
                  <div style='width:72px;height:72px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;'>
                    <span style='font-size:36px;'>✅</span>
                  </div>
                  <h2 style='color:#1f2937;font-size:22px;font-weight:700;margin:0 0 8px;'>Paiement confirmé !</h2>
                  <p style='color:#6b7280;font-size:15px;margin:0;'>Votre paiement a été traité avec succès.</p>
                </td>
              </tr>

              <!-- Salutation -->
              <tr>
                <td style='padding:28px 40px 0;'>
                  <p style='color:#374151;font-size:15px;margin:0 0 20px;'>
                    Bonjour <strong>{$nomComplet}</strong>,<br><br>
                    Nous vous confirmons la réception de votre paiement. Voici le récapitulatif de votre transaction :
                  </p>
                </td>
              </tr>

              <!-- Détails du paiement -->
              <tr>
                <td style='padding:0 40px;'>
                  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8faff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
                    <tr>
                      <td style='padding:16px 20px;border-bottom:1px solid #e5e7eb;'>
                        <span style='color:#6b7280;font-size:13px;display:block;margin-bottom:4px;'>Numéro de facture</span>
                        <strong style='color:#1f2937;font-size:15px;font-family:monospace;'>FAC-{$idFacture}</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:16px 20px;border-bottom:1px solid #e5e7eb;'>
                        <span style='color:#6b7280;font-size:13px;display:block;margin-bottom:4px;'>Date du paiement</span>
                        <strong style='color:#1f2937;font-size:15px;'>{$dateCreation}</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:16px 20px;border-bottom:1px solid #e5e7eb;'>
                        <span style='color:#6b7280;font-size:13px;display:block;margin-bottom:4px;'>Montant réglé</span>
                        <strong style='color:#2563EB;font-size:20px;font-weight:700;'>{$montantFormate}</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:16px 20px;border-bottom:1px solid #e5e7eb;'>
                        <span style='color:#6b7280;font-size:13px;display:block;margin-bottom:4px;'>Mode de paiement</span>
                        <strong style='color:#1f2937;font-size:15px;'>💳 Carte bancaire</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:16px 20px;'>
                        <span style='color:#6b7280;font-size:13px;display:block;margin-bottom:4px;'>Prestation</span>
                        <strong style='color:#1f2937;font-size:15px;'>" . htmlspecialchars($facture['nom_type']) . "</strong>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Section ticket numérique -->
              <tr>
                <td style='padding:28px 40px 0;'>
                  <table width='100%' cellpadding='0' cellspacing='0' style='background:linear-gradient(135deg,#eff6ff 0%,#f0fdfa 100%);border:2px dashed #93c5fd;border-radius:16px;'>
                    <tr>
                      <td style='padding:24px;text-align:center;'>
                        <p style='margin:0 0 6px;color:#2563EB;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;'>Ticket numérique</p>
                        <p style='margin:0 0 16px;color:#6b7280;font-size:13px;'>Votre ticket d'accès a été généré avec succès</p>
                        <div style='background:#1f2937;border-radius:12px;padding:14px 24px;display:inline-block;margin-bottom:16px;'>
                          <span style='color:#14B8A6;font-family:monospace;font-size:22px;font-weight:700;letter-spacing:3px;'>#{$idTicket}</span>
                        </div>
                        <table width='100%' cellpadding='0' cellspacing='0'>
                          <tr>
                            <td style='text-align:center;padding:0 12px;'>
                              <span style='color:#6b7280;font-size:12px;display:block;'>Date d'émission</span>
                              <strong style='color:#374151;font-size:13px;'>{$dateCreation}</strong>
                            </td>
                            <td style='border-left:1px solid #d1d5db;text-align:center;padding:0 12px;'>
                              <span style='color:#6b7280;font-size:12px;display:block;'>Date d'expiration</span>
                              <strong style='color:#374151;font-size:13px;'>{$dateExpiration}</strong>
                            </td>
                          </tr>
                        </table>
                        <p style='margin:16px 0 0;font-size:12px;color:#9ca3af;'>Présentez ce numéro lors de votre admission</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- Message de clôture -->
              <tr>
                <td style='padding:28px 40px;'>
                  <p style='color:#374151;font-size:14px;margin:0 0 16px;'>
                    Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter.
                  </p>
                  <p style='color:#374151;font-size:14px;margin:0;'>
                    Merci pour votre confiance.<br>
                    <strong>Cordialement,<br>L'équipe JumeauNum</strong>
                  </p>
                </td>
              </tr>

              <!-- Pied de page -->
              <tr>
                <td style='background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 40px;text-align:center;'>
                  <p style='color:#9ca3af;font-size:12px;margin:0;'>
                    © 2025 JumeauNum · Plateforme hospitalière numérique<br>
                    <a href='mailto:jumeaunum@lumina.com' style='color:#2563EB;text-decoration:none;'>jumeaunum@lumina.com</a>
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>
    ";

    // Version texte brut (alternative pour les clients mail qui n'affichent pas le HTML)
    $texteAlternatif = "
Bonjour {$nomComplet},

Nous vous confirmons la réception de votre paiement.

DETAILS DU PAIEMENT :
- Numéro de facture : FAC-{$idFacture}
- Date du paiement : {$dateCreation}
- Montant : {$montantFormate}
- Mode de paiement : Carte bancaire
- Prestation : {$facture['nom_type']}

TICKET NUMERIQUE :
- Numéro de ticket : #{$idTicket}
- Date d'émission : {$dateCreation}
- Date d'expiration : {$dateExpiration}

Votre ticket est valable jusqu'au {$dateExpiration}.
Présentez ce numéro lors de votre admission.

Si vous avez des questions, contactez-nous à jumeaunum@lumina.com

Merci pour votre confiance.
Cordialement,
L'équipe JumeauNum
    ";

    // Configuration PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUser;
        $mail->Password   = $gmailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Important : réduit les chances d'aller en spam
        $mail->XMailer    = ' '; // Masque le header X-Mailer généré par PHPMailer

        $mail->setFrom($gmailUser, 'JumeauNum');
        $mail->addAddress($destinataire['email'], $nomComplet);
        $mail->addReplyTo('jumeaunum@lumina.com', 'Support JumeauNum');

        $mail->isHTML(true);
        $mail->Subject = "Confirmation de paiement - JumeauNum - FAC-{$idFacture}";
        $mail->Body    = $htmlBody;
        $mail->AltBody = $texteAlternatif;

        $mail->send();
        return ['success' => true, 'message' => 'Email envoyé avec succès.'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur email : ' . $mail->ErrorInfo];
    }
}
