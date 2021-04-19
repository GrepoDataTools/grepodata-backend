<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\MailJobs;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Logger\Pushbullet;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Model\Operation_log;
use Illuminate\Support\Facades\Log;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started mailer");

$Start = Carbon::now();
$oCronStatus = Common::markAsRunning(__FILE__, 2*60, false);

$MaxAttempts = 1;

// process mail jobs
$Count = 0;
$Fails = 0;
try {
  /** @var \Grepodata\Library\Model\MailJobs $oMail */
  $oMail = MailJobs::NextUnprocessedByIdAsc(-1, $MaxAttempts);
  while ($oMail !== false && $oMail !== null && $Count < 4) {
    $oMail->processing = true;
    $oMail->attempts = $oMail->attempts + 1;
    $oMail->save();

    // Try sending mail
    try {
      $Count++;
      $Result = Client::RetryMail($oMail);
      if ($Result <= 0) {
        $Fails++;
      } else {
        // Mark as processed
        if ($oMail->to_mail !== 'admin@grepodata.com') {
          Logger::warning("Mailer script processed message successfully.");
        }
        $oMail->processing = false;
        $oMail->processed = true;
        $oMail->save();
      }
    } catch (\Exception $e) {
      Logger::error("Error sending mail message: " . $e->getMessage());
      $oMail->processing = false;
      $oMail->processed = false;
      $oMail->save();
      continue;
    }

    $oMail = MailJobs::NextUnprocessedByIdAsc($oMail->id, $MaxAttempts);
  }
} catch (\Exception $e) {
  Logger::error("Error processing mail jobs: " . $e->getMessage());
}

// Pushbullet notify errors
try {
  $last_error_check = Carbon::parse($oCronStatus->last_error_check);
  $current_error_check = Carbon::now();

  $MinsSinceCheck = $current_error_check->diffInMinutes($last_error_check);
  if ($MinsSinceCheck >= 5) {
    // check every 5 mins
    $aErrors = Operation_log::where('created_at', '>=', $last_error_check)
      ->where('created_at', '<=', $current_error_check)
      ->where('level', '=', 1)
      ->get();

    $NumErrors = count($aErrors);
    $MaxCharsPerError = 200;
    $MaxErrorsPrinted = 10;
    $ErrorsPrinted = 0;
    if ($NumErrors > 0) {
      Logger::warning("Found ".$NumErrors." errors to notify.");
      $pbMessage = $NumErrors . " errors since " . $last_error_check->format('Y-m-d H:i:s') . ":\n";
      /** @var Operation_log $oError */
      foreach ($aErrors as $oError) {
        $ErrorsPrinted += 1;
        if ($ErrorsPrinted <= $MaxErrorsPrinted) {
          $pbMessage .= "\t" . $oError->created_at->format('Y-m-d H:i:s') . " - " . substr($oError->message, 0, $MaxCharsPerError) . "\n";
        }
      }
      Pushbullet::SendPushMessage($pbMessage, 'GD error');
    }

    $oCronStatus->last_error_check = $current_error_check;
    $oCronStatus->save();
  }
} catch (\Exception $e) {
  Logger::error("Error sending logger notification via pushbullet: " . $e->getMessage());
}

Logger::debugInfo("Finished successful execution of mailer. Messages processed: " . $Count . ", failures: " . $Fails);
Common::endExecution(__FILE__, $Start);
