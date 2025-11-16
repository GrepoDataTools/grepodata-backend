<?php
//
//namespace Grepodata\Cron;
//
//use Carbon\Carbon;
//use Grepodata\Application\API\Route\Authentication;
//use Grepodata\Library\Controller\MailJobs;
//use Grepodata\Library\Controller\User;
//use Grepodata\Library\Cron\Common;
//use Grepodata\Library\Import\Daily;
//use Grepodata\Library\Logger\Logger;
//use Grepodata\Library\Logger\Pushbullet;
//use Grepodata\Library\Mail\Client;
//use Grepodata\Library\Model\Operation_log;
//use Illuminate\Support\Facades\Log;
//
//if (PHP_SAPI !== 'cli') {
//  die('not allowed');
//}
//
//require(__DIR__ . '/../config.php');
//
//Logger::enableDebug();
//Logger::debugInfo("Started activation mail sender");
//
//try {
//  $oUsers = \Grepodata\Library\Model\User::where('id', '>=', 40194)
//    ->where('id', '<=', 40354)
//    ->where('is_confirmed', '=', 0)
//    ->whereNull('is_deleted')
//    ->get();
//  error_log(sizeof($oUsers));
//
//  foreach ($oUsers as $oUser) {
//    print $oUser->id . " - " . $oUser->username . "\n";
//    Authentication::sendRegistrationMail($oUser);
//  }
//
//} catch (\Exception $e) {
//  Logger::error("Error sending activation mail: " . $e->getMessage());
//}
//
//Logger::debugInfo("Finished successful execution of account activation mailer.");
