<?php
    require '../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $host     = getenv('MAILER_HOST');
    $port     = getenv('MAILER_PORT');
    $username = getenv('MAILER_USERNAME');
    $password = getenv('MAILER_PASSWORD');
    $from     = getenv('MAILER_FROM_ADDRESS');
    $fromName = getenv('MAILER_FROM_NAME');

    $to = 'montezbroughton@icloud.com';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';

        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = 'html';

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'MailerSend SMTP Test';
        $mail->Body    = '<p>If you see this email, SMTP is working! ✅</p>';

        $mail->send();
        echo "Test email sent successfully!";
    } catch (Exception $e) {
        echo "Test email failed: " . $mail->ErrorInfo;
    }