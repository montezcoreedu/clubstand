<?php

require ("../../vendor/autoload.php");

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;

// Init MailerSend
$mailersend = new MailerSend([
    'api_key' => getenv('MAILERSEND_API_KEY'),
]);

$toEmail = 'montezbroughton@icloud.com';

// Build email
$emailParams = (new EmailParams())
    ->setFrom('no-reply@test-51ndgwvqv1qlzqx8.mlsender.net') // test domain sender
    ->setFromName('Core Communication')
    ->setRecipients([
        new Recipient($toEmail, 'Montez')
    ])
    ->setSubject('MailerSend API Test ✅')
    ->setHtml('<p>If you see this email, the MailerSend API is working! 🚀</p>');

try {
    $mailersend->email->send($emailParams);
    echo "✅ Test email sent successfully via API!";
} catch (Exception $e) {
    echo "❌ API email failed: " . $e->getMessage();
}