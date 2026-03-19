<?php
// Example: sending the built email via SMTP using PHPMailer
//
// Install PHPMailer: composer require phpmailer/phpmailer
// Then configure SMTP settings below.
//
// Alternatives:
//   - Symfony Mailer: symfony/mailer
//   - Native: mail() function (not recommended for production)
//   - SendGrid: sendgrid/sendgrid

// require_once __DIR__ . '/vendor/autoload.php';
// use PHPMailer\PHPMailer\PHPMailer;

$html = file_get_contents('dist/welcome-merged.html');
$text = file_get_contents('dist/welcome.txt');

// $mail = new PHPMailer(true);
// $mail->isSMTP();
// $mail->Host       = 'smtp.example.com';
// $mail->SMTPAuth   = true;
// $mail->Username   = 'your-username';
// $mail->Password   = 'your-password';
// $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
// $mail->Port       = 587;
//
// $mail->setFrom('noreply@example.com', 'My App');
// $mail->addAddress('alice@example.com');
// $mail->Subject = 'Welcome!';
// $mail->isHTML(true);
// $mail->Body    = $html;
// $mail->AltBody = $text;
//
// $mail->send();
// echo "sent\n";

echo "SMTP config not set — edit send.php with your credentials.\n";
echo "HTML length: " . strlen($html) . " bytes\n";
echo "Text length: " . strlen($text) . " bytes\n";
