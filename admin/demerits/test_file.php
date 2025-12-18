<?php
require '../../vendor/autoload.php';

use Mailgun\Mailgun;

$mg = Mailgun::create(getenv('MAILGUN_API_KEY') ?: 'MAILGUN_API_KEY');

$result = $mg->messages()->send(
	'sandbox1d1cddcc20394d58bc11904f63a47040.mailgun.org',
	[
		'from' => 'Mailgun Sandbox <postmaster@sandbox1d1cddcc20394d58bc11904f63a47040.mailgun.org>',
		'to' => 'Core Education <coresolutionsedu@gmail.com>',
		'subject' => 'Hello Core Education',
		'text' => 'Congratulations Core Education, you just sent an email with Mailgun! You are truly awesome!'
	]
);

print_r($result->getMessage());