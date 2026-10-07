<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\AdSense\Client;
use Grepodata\Library\Controller\Donation;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;

/**
 * One-time setup: generate PRIVATE_ADSENSE_REFRESH_TOKEN via Google OAuth 2.0 Playground
 * (https://developers.google.com/oauthplayground):
 *   1. Click the gear icon (top right) -> check "Use your own OAuth credentials" -> enter
 *      PRIVATE_ADSENSE_CLIENT_ID / PRIVATE_ADSENSE_CLIENT_SECRET.
 *   2. In the left panel, scope input, enter: https://www.googleapis.com/auth/adsense.readonly
 *      -> Authorize APIs -> sign in with the Google account that owns the AdSense account.
 *   3. Click "Exchange authorization code for tokens" -> copy the "Refresh token" value.
 *   4. Paste it into PRIVATE_ADSENSE_REFRESH_TOKEN in Software/config.private.php.
 * The token does not expire by time; it keeps working indefinitely as long as the OAuth consent
 * screen is in "Production" status (Testing-status tokens expire after 7 days) and access isn't
 * revoked at https://myaccount.google.com/permissions.
 */

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started adsense earnings check");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 55);

try {
  $From = Carbon::now()->subMonths(2)->startOfMonth(); // last 3 months
  // $From = Carbon::now()->subYears(2)->startOfMonth(); // force override: last 2 years
  $MonthlyEarnings = Client::getMonthlyEarnings($From);
  foreach ($MonthlyEarnings as $MonthKey => $Amount) {
    Donation::UpsertAdsenseEarnings($MonthKey, $Amount);
    Logger::debugInfo("Updated adsense earnings for " . $MonthKey . ": " . $Amount);
  }
} catch (\Exception $e) {
  Logger::error("Error processing adsense earnings check (" . $e->getMessage() . ")");
}

Logger::debugInfo("Finished adsense earnings check.");
Common::endExecution(__FILE__, $Start);
