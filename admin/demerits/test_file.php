<?php

require __DIR__ . '/../../vendor/autoload.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Exceptions\MailerSendException; // import exception

$mailersend = new MailerSend([
    'api_key' => getenv('MAILERSEND_API_KEY'),
]);

$toEmail = 'montezbroughton@icloud.com';

$emailParams = (new EmailParams())
    ->setFrom('no-reply@test-51ndgwvqv1qlzqx8.mlsender.net') // must be full valid email
    ->setFromName('Core Communication')
    ->setRecipients([
        new Recipient($toEmail, 'Montez')
    ])
    ->setSubject('MailerSend API Test ✅')
    ->setHtml('<p>If you see this email, the MailerSend API is working! 🚀</p>');

try {
    $mailersend->email->send($emailParams);
    echo "✅ Test email sent successfully via API!";
} catch (MailerSendException $e) {
    echo "❌ API email failed: " . $e->getMessage();
}