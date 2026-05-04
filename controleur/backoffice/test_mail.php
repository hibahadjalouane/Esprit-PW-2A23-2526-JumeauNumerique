<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

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
    $mail->addAddress('yassinechaari52@gmail.com'); // envoyer à vous-même pour tester
    $mail->isHTML(true);
    $mail->Subject = 'Test JumeauNum';
    $mail->Body    = '<h2>✅ Email fonctionne !</h2><p>PHPMailer est bien configuré.</p>';
    $mail->send();
    echo '✅ Email envoyé avec succès !';
} catch (Exception $e) {
    echo '❌ Erreur : ' . $mail->ErrorInfo;
}
?>