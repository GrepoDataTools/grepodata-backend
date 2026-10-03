<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Grepodata\Library\Controller\Donation as DonationController;
use Grepodata\Library\Redis\RedisClient;

class Donation extends \Grepodata\Library\Router\BaseRoute
{

  public static function GetAllDonationsGET()
  {
    $Cached = RedisClient::GetKey(RedisClient::DONATIONS_LIST_KEY);
    if ($Cached !== false) {
      return self::OutputJson(json_decode($Cached, true));
    }

    $aDonations = DonationController::GetAllPublicDonations();
    $aResponse = array(
      'count' => count($aDonations),
      'items' => $aDonations
    );

    RedisClient::SetKey(RedisClient::DONATIONS_LIST_KEY, json_encode($aResponse), 3600);
    return self::OutputJson($aResponse);
  }

  public static function GetDonationStateGET()
  {
    $CacheKey = RedisClient::DONATIONS_MONTH_SUM_PREFIX . Carbon::now()->format('Y-m');
    $Cached = RedisClient::GetKey($CacheKey);
    if ($Cached !== false) {
      return self::OutputJson(json_decode($Cached, true));
    }

    $aResponse = array(
      'total' => round(DonationController::SumCurrentMonth(), 2)
    );

    RedisClient::SetKey($CacheKey, json_encode($aResponse), 3600);
    return self::OutputJson($aResponse);
  }

}

