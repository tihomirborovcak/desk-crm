<?php
/**
 * Email konfiguracija za slanje obavijesti
 */

define('SMTP_HOST', 'mail.zagorski-list.net');
define('SMTP_PORT', 465);
define('SMTP_USER', 'facebook_poruke@zagorski-list.net');
define('SMTP_PASS', 'tojemojalozinka');
define('SMTP_FROM', 'facebook_poruke@zagorski-list.net');
define('SMTP_FROM_NAME', 'Facebook Poruke');

// Primatelji obavijesti (može biti više emailova odvojenih zarezom)
define('NOTIFICATION_EMAILS', 'facebook_poruke@zagorski-list.net');

/**
 * Pošalji email preko SMTP
 */
if (!function_exists('sendEmail')) {
function sendEmail($to, $subject, $body, $isHtml = true) {
    // Koristi PHPMailer ako postoji, inače fallback na mail()
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        return sendWithPHPMailer($to, $subject, $body, $isHtml);
    }

    // Fallback - koristi fsockopen za direktni SMTP
    return sendWithSocket($to, $subject, $body, $isHtml);
}
}

/**
 * Pošalji email preko socketa (bez PHPMailer)
 */
if (!function_exists('sendWithSocket')) {
function sendWithSocket($to, $subject, $body, $isHtml = true) {
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";

    if ($isHtml) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }

    // Jednostavni SMTP preko ssl://
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen('ssl://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);

    if (!$socket) {
        error_log("SMTP Connection failed: $errstr ($errno)");
        return false;
    }

    // Čitaj pozdrav
    fgets($socket, 515);

    // EHLO
    fwrite($socket, "EHLO " . SMTP_HOST . "\r\n");
    while ($line = fgets($socket, 515)) {
        if (substr($line, 3, 1) == ' ') break;
    }

    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    fgets($socket, 515);

    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    fgets($socket, 515);

    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    $authResponse = fgets($socket, 515);

    if (substr($authResponse, 0, 3) != '235') {
        error_log("SMTP Auth failed: $authResponse");
        fclose($socket);
        return false;
    }

    // MAIL FROM
    fwrite($socket, "MAIL FROM:<" . SMTP_FROM . ">\r\n");
    fgets($socket, 515);

    // RCPT TO
    $recipients = explode(',', $to);
    foreach ($recipients as $recipient) {
        fwrite($socket, "RCPT TO:<" . trim($recipient) . ">\r\n");
        fgets($socket, 515);
    }

    // DATA
    fwrite($socket, "DATA\r\n");
    fgets($socket, 515);

    // Poruka
    $message = "Subject: $subject\r\n";
    $message .= $headers;
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.\r\n";

    fwrite($socket, $message);
    fgets($socket, 515);

    // QUIT
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}
}

/**
 * Pošalji email preko PHPMailer (ako je instaliran)
 */
if (!function_exists('sendWithPHPMailer')) {
function sendWithPHPMailer($to, $subject, $body, $isHtml = true) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);

        $recipients = explode(',', $to);
        foreach ($recipients as $recipient) {
            $mail->addAddress(trim($recipient));
        }

        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
}
