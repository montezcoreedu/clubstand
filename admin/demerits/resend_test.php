<?php
require_once __DIR__ . "/../../vendor/autoload.php"; // make sure path is correct

use Resend\Resend;

$resend = new Resend(getenv('RESEND_API_KEY'));
var_dump($resend);

