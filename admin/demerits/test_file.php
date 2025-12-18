<?php
    require __DIR__ . '/../../vendor/autoload.php';

    use Mailgun\Mailgun;

    $mg = Mailgun::create(getenv('MAILGUN_API_KEY'));

    $result = $mg->messages()->send(
        'corecommunication.org',
        [
            'from'    => 'Core Communication <no-reply@corecommunication.org>',
            'to'      => 'montezbroughton@icloud.com',
            'subject' => 'Mailgun is LIVE 🚀',
            'text'    => 'If you got this email, Mailgun + Render + API is officially working.',
        ]
    );

    echo $result->getMessage();