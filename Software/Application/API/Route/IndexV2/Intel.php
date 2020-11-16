<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Intel extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns all intel collected by this user, ordered by index date
   */
  public static function GetIntelForUserGet()
  {
    try {
      $aParams = self::validateParams(array('access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $From = $aParams['from'] ?? 0;
      $Size = $aParams['size'] ?? 20;
      $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::allByUser($oUser, $From, $Size);

      $aIntelData = array();
      $aWorlds = array();
      foreach ($aIntel as $oIntel) {
        if (!key_exists($oIntel->world, $aWorlds)) {
          $aWorlds[$oIntel->world] = World::getWorldById($oIntel->world);
        }
        $aBuildings = array();
        $aTownIntelRecord = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oIntel, $aWorlds[$oIntel->world], $aBuildings);
        $aTownIntelRecord['source_type'] = $oIntel->source_type;
        $aTownIntelRecord['parsed'] = ($oIntel->parsing_failed==0?true:false);
        $aTownIntelRecord['world'] = $oIntel->world;
        $aTownIntelRecord['town_id'] = $oIntel->town_id;
        $aTownIntelRecord['town_name'] = $oIntel->town_name;
        $aTownIntelRecord['player_id'] = $oIntel->player_id;
        $aTownIntelRecord['player_name'] = $oIntel->player_name;
        $aTownIntelRecord['alliance_id'] = $oIntel->alliance_id;
        $aTownIntelRecord['alliance_name'] = Alliance::first($oIntel->alliance_id, $oIntel->world)->name ?? '';
        $aIntelData[] = $aTownIntelRecord;
      }

      if (sizeof($aIntel)>$Size) {
        $Size = $From+$Size;
        $Size .= '+';
        array_pop($aIntelData);
      } else {
        $Size = $From+sizeof($aIntel);
      }

      $aResponse = array(
        'size'    => $Size,
        'items'   => $aIntelData
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'town_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $DummyResponse = "{
          \"world\": \"nl78\",
          \"town_id\": \"66\",
          \"name\": \"Aemon Targaryen*\",
          \"ix\": 500,
          \"iy\": 516,
          \"player_id\": 1301519,
          \"alliance_id\": 1043,
          \"player_name\": \"jorah-mormont\",
          \"has_stonehail\": false,
          \"notes\": [],
          \"buildings\": {
              \"wall\": {
                  \"level\": 0,
                  \"date\": \"06-07-20 19:03:07\"
              }
          },
          \"intel\": [
              {
                  \"id\": 2158602,
                  \"deleted\": false,
                  \"sort_date\": \"2020-08-01T07:14:39.000000Z\",
                  \"date\": \"01-08-20 07:14:39\",
                  \"units\": [
                      {
                          \"name\": \"manticore\",
                          \"count\": 65,
                          \"killed\": 65
                      }
                  ],
                  \"type\": \"attack_on_conquest\",
                  \"silver\": \"\",
                  \"wall\": \"\",
                  \"stonehail\": null,
                  \"conquest_id\": 10942,
                  \"hero\": \"\",
                  \"god\": \"zeus\",
                  \"cost\": 125
              },
              {
                  \"id\": 2017446,
                  \"deleted\": false,
                  \"sort_date\": \"2020-07-06T19:03:07.000000Z\",
                  \"date\": \"06-07-20 19:03:07\",
                  \"units\": [
                      {
                          \"name\": \"militia\",
                          \"count\": 375,
                          \"killed\": 375
                      }
                  ],
                  \"type\": \"friendly_attack\",
                  \"silver\": \"\",
                  \"wall\": \"0\",
                  \"stonehail\": null,
                  \"conquest_id\": 0,
                  \"hero\": \"helen\",
                  \"god\": \"\",
                  \"cost\": 37
              },
              {
                  \"id\": 2013670,
                  \"deleted\": false,
                  \"sort_date\": \"2020-07-05T22:21:18.000000Z\",
                  \"date\": \"05-07-20 22:21:18\",
                  \"units\": [
                      {
                          \"name\": \"attack_ship\",
                          \"count\": 58,
                          \"killed\": 58
                      }
                  ],
                  \"type\": \"attack_on_conquest\",
                  \"silver\": \"\",
                  \"wall\": \"\",
                  \"stonehail\": null,
                  \"conquest_id\": 8858,
                  \"hero\": \"\",
                  \"god\": \"\",
                  \"cost\": 17
              },
              {
                  \"id\": 2013668,
                  \"deleted\": false,
                  \"sort_date\": \"2020-07-05T22:13:10.000000Z\",
                  \"date\": \"05-07-20 22:13:10\",
                  \"units\": [
                      {
                          \"name\": \"manticore\",
                          \"count\": 31,
                          \"killed\": 31
                      }
                  ],
                  \"type\": \"attack_on_conquest\",
                  \"silver\": \"\",
                  \"wall\": \"\",
                  \"stonehail\": null,
                  \"conquest_id\": 8858,
                  \"hero\": \"\",
                  \"god\": \"zeus\",
                  \"cost\": 12
              },
              {
                  \"id\": 2010335,
                  \"deleted\": false,
                  \"sort_date\": \"2020-07-05T15:20:39.000000Z\",
                  \"date\": \"05-07-20 15:20:39\",
                  \"units\": [
                      {
                          \"name\": \"sword\",
                          \"count\": 1,
                          \"killed\": 1
                      },
                      {
                          \"name\": \"militia\",
                          \"count\": 375,
                          \"killed\": 375
                      },
                      {
                          \"name\": \"attack_ship\",
                          \"count\": 218,
                          \"killed\": 0
                      }
                  ],
                  \"type\": \"friendly_attack\",
                  \"silver\": \"\",
                  \"wall\": \"0\",
                  \"stonehail\": null,
                  \"conquest_id\": 0,
                  \"hero\": \"helen\",
                  \"god\": \"\",
                  \"cost\": 102
              },
              {
                  \"id\": 2004033,
                  \"deleted\": false,
                  \"sort_date\": \"2020-07-04T15:07:23.000000Z\",
                  \"date\": \"04-07-20 15:07:23\",
                  \"units\": [],
                  \"type\": \"friendly_attack\",
                  \"silver\": \"\",
                  \"wall\": \"0\",
                  \"stonehail\": null,
                  \"conquest_id\": 0,
                  \"hero\": \"helen\",
                  \"god\": \"\",
                  \"cost\": 0
              },
              {
                  \"id\": 1978700,
                  \"deleted\": false,
                  \"sort_date\": \"2020-06-29T21:33:41.000000Z\",
                  \"date\": \"29-06-20 21:33:41\",
                  \"units\": [
                      {
                          \"name\": \"rider\",
                          \"count\": 126,
                          \"killed\": 126
                      },
                      {
                          \"name\": \"manticore\",
                          \"count\": 40,
                          \"killed\": 40
                      }
                  ],
                  \"type\": \"attack_on_conquest\",
                  \"silver\": \"\",
                  \"wall\": \"\",
                  \"stonehail\": null,
                  \"conquest_id\": 8298,
                  \"hero\": \"\",
                  \"god\": \"zeus\",
                  \"cost\": 28
              },
              {
                  \"id\": 1835771,
                  \"deleted\": false,
                  \"sort_date\": \"2020-06-06T07:00:48.000000Z\",
                  \"date\": \"06-06-20 07:00:48\",
                  \"units\": [
                      {
                          \"name\": \"manticore\",
                          \"count\": 68,
                          \"killed\": 68
                      }
                  ],
                  \"type\": \"attack_on_conquest\",
                  \"silver\": \"\",
                  \"wall\": \"\",
                  \"stonehail\": null,
                  \"conquest_id\": 6118,
                  \"hero\": \"deimos\",
                  \"god\": \"zeus\",
                  \"cost\": 27
              }
          ],
          \"latest_version\": \"4.0.3\",
          \"update_message\": \"Bugfixes and index security improvements.\",
          \"has_intel\": true
      }";

      die($DummyResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}