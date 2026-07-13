<?php
// Example: sending example 05's digest email via SMTP using PHPMailer.
//
// Run `composer run examples` first (or at least
// `php examples/05-plain-text/run.php`) so dist/05-plain-text/digest.html
// and digest.txt exist.
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

$htmlPath = __DIR__ . '/dist/05-plain-text/digest.html';
$textPath = __DIR__ . '/dist/05-plain-text/digest.txt';

if (!is_file($htmlPath) || !is_file($textPath)) {
    fwrite(STDERR, "Missing dist/05-plain-text/digest.{html,txt} — run `composer run examples` first.\n");
    exit(1);
}

$html = file_get_contents($htmlPath);
$text = file_get_contents($textPath);

// $mail = new PHPMailer(true);
// $mail->isSMTP();
// $mail->Host       = 'smtp.example.com';
// $mail->SMTPAuth   = true;
// $mail->Username   = 'your-username';
// $mail->Password   = 'your-password';
// $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
// $mail->Port       = 587;
//
// $mail->setFrom('digest@northwindcoffee.example', 'Northwind Coffee');
// $mail->addAddress('subscriber@example.com');
// $mail->Subject = 'This week at Northwind Coffee';
// $mail->isHTML(true);
// $mail->Body    = $html;  // the HTML part
// $mail->AltBody = $text;  // the plain-text part (from 'plain_text' => true)
//
// $mail->send();
// echo "sent\n";

// What PHPMailer builds under the hood, spelled out by hand: a
// multipart/alternative message with the plain-text part first (least
// capable first) and the HTML part second, joined by a MIME boundary.
$boundary = 'nwc-' . bin2hex(random_bytes(16));
$message = "From: Northwind Coffee <digest@northwindcoffee.example>\r\n"
    . "To: subscriber@example.com\r\n"
    . "Subject: This week at Northwind Coffee\r\n"
    . "MIME-Version: 1.0\r\n"
    . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n"
    . "\r\n"
    . "--{$boundary}\r\n"
    . "Content-Type: text/plain; charset=utf-8\r\n"
    . "\r\n"
    . $text . "\r\n"
    . "--{$boundary}\r\n"
    . "Content-Type: text/html; charset=utf-8\r\n"
    . "\r\n"
    . $html . "\r\n"
    . "--{$boundary}--\r\n";

echo "SMTP config not set — edit send.php with your credentials.\n";
echo "HTML part:      " . strlen($html) . " bytes\n";
echo "Text part:       " . strlen($text) . " bytes\n";
echo "Multipart total: " . strlen($message) . " bytes\n";
