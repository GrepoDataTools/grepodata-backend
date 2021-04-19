<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;

$t = Carbon::now()->subDay();
$t2 = Carbon::now();


$Result = false;
$Token = 'zzz';
try {
  $Result = Client::SendMail(
    'admin@grepodata.com',
    'admin@grepodata.com',
    'GrepoData Account Confirmation',
    'Hi,<br/>
<br/>
You are receiving this message because an account was created on grepodata.com using this email address.<br/>
<br/>
Please click on the following link to confirm your account:<br/>
<br/>
<a href="https://api.grepodata.com/confirm?token='.$Token.'">https://api.grepodata.com/confirm?token='.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our account registration form.<br/>
You can ignore this email if you no longer wish to create an account for our website.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
    null,
    false,
    true
  );
} catch (\Exception $e) {
  Logger::error("Error sending sendgrid request " . $e->getMessage() . " - " . $e->getTraceAsString());
}
$t=2;
