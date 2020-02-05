<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\MailJobs;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started mailer");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 24*60, false);

// process mail jobs
$Count = 0;
$Fails = 0;
try {
  /** @var \Grepodata\Library\Model\MailJobs $oMail */
  $oMail = MailJobs::NextUnprocessedByIdAsc(-1);
  while ($oMail !== false && $oMail !== null && $Count < 4) {
    $oMail->processing = true;
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

    $oMail = MailJobs::NextUnprocessedByIdAsc($oMail->id);
  }
} catch (\Exception $e) {
  Logger::error("Error processing mail jobs: " . $e->getMessage());
}

Logger::debugInfo("Finished successful execution of mailer. Messages processed: " . $Count . ", failures: " . $Fails);
Common::endExecution(__FILE__, $Start);