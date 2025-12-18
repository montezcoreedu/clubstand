<?php

require __DIR__ . '/../../vendor/autoload.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Exceptions\MailerSendException;

// Init MailerSend with your API key
$mailersend = new MailerSend([
    'api_key' => getenv('MAILERSEND_API_KEY'),
]);

$toEmail = 'montezbroughton@icloud.com';

// Build recipient list
$recipients = [
    new Recipient($toEmail, 'Montez'),
];

// Build email parameters
$emailParams = (new EmailParams())
    ->setFrom('no-reply@test-51ndgwvqv1qlzqx8.mlsender.net') // must be exact test domain email
    ->setFromName('Core Communication')
    ->setRecipients($recipients)
    ->setSubject('MailerSend API Test ✅')
    ->setHtml('<p>If you see this email, the MailerSend API is working! 🚀</p>')
    ->setText('If you see this email, the MailerSend API is working! 🚀');

try {
    $mailersend->email->send($emailParams);
    echo "✅ Test email sent successfully via API!";
} catch (MailerSendException $e) {
    echo "❌ API email failed: " . $e->getMessage();
}