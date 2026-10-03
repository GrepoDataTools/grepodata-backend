<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\BuyMeACoffee\Client;
use Grepodata\Library\Controller\Donation;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started donation check");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 55);

try {
  // Pagination across multiple pages is not needed yet; new donations appear on page 1
  $Supporters = Client::getSupporters();
  foreach ($Supporters as $Supporter) {
    $Amount = $Supporter['support_coffees'] * (float)$Supporter['support_coffee_price'];
    $Date = Carbon::parse($Supporter['support_created_on']);

    $oDonation = Donation::AddDonation(
      $Supporter['support_id'],
      $Amount,
      $Supporter['supporter_name'],
      $Date,
      'buymeacoffee',
      $Supporter['support_note'],
      $Supporter['country']
    );

    if ($oDonation !== false) {
      Logger::error("New donation: " . json_encode($Supporter));
    }
  }
} catch (\Exception $e) {
  Logger::error("Error processing donation check (" . $e->getMessage() . ")");
}

Logger::debugInfo("Finished donation check.");
Common::endExecution(__FILE__, $Start);
