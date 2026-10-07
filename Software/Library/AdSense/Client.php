<?php

namespace Grepodata\Library\AdSense;

use Carbon\Carbon;
use Google\Service\AdSense;

class Client
{
  const APP_NAME = 'Grepodata AdSense Earnings';

  /**
   * Builds an authenticated Google API client using the stored offline refresh token.
   * A new access token is minted on every call; no token is cached to disk.
   * @return \Google\Client
   * @throws \Exception
   */
  private static function getClient()
  {
    $client = new \Google\Client();
    $client->setApplicationName(self::APP_NAME);
    $client->setScopes(array(AdSense::ADSENSE_READONLY));
    $client->setAuthConfig(array(
      'client_id'     => PRIVATE_ADSENSE_CLIENT_ID,
      'client_secret' => PRIVATE_ADSENSE_CLIENT_SECRET,
    ));
    $client->setAccessType('offline');

    $AccessToken = $client->fetchAccessTokenWithRefreshToken(PRIVATE_ADSENSE_REFRESH_TOKEN);
    if (isset($AccessToken['error'])) {
      throw new \Exception('AdSense token refresh failed: ' . $AccessToken['error_description']);
    }
    $client->setAccessToken($AccessToken);

    return $client;
  }

  /**
   * Fetches estimated earnings grouped by month from the given start date (forced to the
   * 1st of that month) through today. Defaults to the start of the current month.
   * @param \Carbon\Carbon|string|null $From
   * @return array<string, float> Example: ['2026-01' => 150.50, '2026-02' => 200.00]
   * @throws \Exception
   */
  public static function getMonthlyEarnings($From = null)
  {
    $StartDate = is_null($From) ? Carbon::now() : Carbon::parse($From);
    $StartDate->startOfMonth();
    $Now = Carbon::now();

    $client = self::getClient();
    $service = new AdSense($client);

    $Accounts = $service->accounts->listAccounts()->getAccounts();
    if (empty($Accounts)) {
      throw new \Exception('No AdSense accounts found for this authorized user.');
    }
    $AccountName = $Accounts[0]->getName();

    $optParams = array(
      'dateRange'       => 'CUSTOM',
      'startDate.year'  => $StartDate->year,
      'startDate.month' => $StartDate->month,
      'startDate.day'   => $StartDate->day,
      'endDate.year'    => $Now->year,
      'endDate.month'   => $Now->month,
      'endDate.day'     => $Now->day,
      'metrics'         => array('ESTIMATED_EARNINGS'),
      'dimensions'      => array('MONTH'),
    );

    $Report = $service->accounts_reports->generate($AccountName, $optParams);
    $Rows = $Report->getRows();

    $MonthlyData = array();
    if (!empty($Rows)) {
      foreach ($Rows as $Row) {
        $Cells = $Row->getCells();
        $Month = $Cells[0]->getValue(); // Format: YYYY-MM
        $Earnings = (float)$Cells[1]->getValue();
        $MonthlyData[$Month] = $Earnings * PRIVATE_ADSENSE_CURRENCY_RATE;
      }
    }

    return $MonthlyData;
  }
}
