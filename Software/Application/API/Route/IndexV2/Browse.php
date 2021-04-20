<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Redis\RedisClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Browse extends \Grepodata\Library\Router\BaseRoute
{
  const aOffKeys = array(
    // Slingeraar
    'slinger' => array(
      'keys' => array(
        'sling',
        'slinger',
      ),
      'min' => 35
    ),
    // Ruiter
    'rider' => array(
      'keys' => array(
        'caval',
        'rider',
      ),
      'min' => 20
    ),
    // Kata
    'catapult' => array(
      'keys' => array(
        'kata',
        'catapult'
      ),
      'min' => 3,
      'type' => ['enemy_attack', 'spy', 'attack_on_conquest']
    ),
    // Gezant
    'godsent' => array(
      'keys' => array(
        'gezant',
        'godsent'
      ),
      'min' => 10,
      'type' => ['enemy_attack', 'spy', 'attack_on_conquest']
    ),
    // Hopliet
    'hoplite' => array(
      'keys' => array(
        'hoplite',
        'spear',
      ),
      'min' => 50,
      'max'=> 5000,
      'type' => ['enemy_attack', 'attack_on_conquest'] // dont detect in spies!
    ),
    // Strijdwagen
    'chariot' => array(
      'keys' => array(
        'chariot',
        'strijd',
      ),
      'min' => 20,
      'max'=> 1000,
      'type' => ['enemy_attack', 'attack_on_conquest']
    ),
  );
  const aDefKeys = array(
    // Boog
    'archer' => array(
      'keys' => array(
        'bow',
        'archer',
      ),
      'min' => 50,
      'max'=> 5000
    ),
    // Zwaard
    'sword' => array(
      'keys' => array(
        'sword'
      ),
      'min' => 50,
      'max'=> 5000
    ),
    // Hopliet
    'hoplite' => array(
      'keys' => array(
        'hoplite',
        'spear',
      ),
      'min' => 50,
      'max'=> 5000,
      'type' => ['spy', 'support', 'friendly_attack']
    ),
    // Strijdwagen
    'chariot' => array(
      'keys' => array(
        'chariot',
        'strijd',
      ),
      'min' => 20,
      'max'=> 1000,
      'type' => ['spy', 'support', 'friendly_attack']
    ),
    // Gezant
    'godsent' => array(
      'keys' => array(
        'gezant',
        'godsent'
      ),
      'min' => 10,
      'max'=> 1000,
      'type' => ['friendly_attack', 'support']
    ),
  );
  const translateMythType = array(
    'sea_monster' => 'sea_monster', 'cyclope' => 'zyklop',
    'harp'      => 'harpy', 'medusa' => 'medusa',
    'mino'      => 'minotaur','manti' => 'manticore',
    'centaur'   => 'centaur', 'pegasus' => 'pegasus',
    'cerberus'  => 'cerberus', 'erinyes' => 'fury',
    'griff'     => 'griffin', 'boar' => 'calydonian_boar'
  );

  public static function GetPlayerGET()
  {
    $aParams = array();
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'player_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // get world
      $oWorld = World::getWorldById($aParams['world']);

      if (is_numeric($aParams['player_id'])) {
        $CachedResponse = RedisClient::GetKey(RedisClient::INDEXER_PLAYER_PREFIX.$aParams['player_id'].$oWorld->grep_id.$oUser->id);
        if ($CachedResponse!=false) {
          $aResponse = json_decode($CachedResponse, true);
          $aResponse['cached_response'] = true;
          return self::OutputJson($aResponse);
        }
      }

      // Get intel
      $Start = round(microtime(true) * 1000);
      $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForPlayer($oUser, $oWorld->grep_id, $aParams['player_id'], true);
      $ElapsedMs = round(microtime(true) * 1000) - $Start;
      if ($aIntel === null || sizeof($aIntel) <= 0) throw new ModelNotFoundException();

      $aResponse = self::FormatBrowseOutput($aIntel, $oWorld);
      $aResponse['script_version'] = USERSCRIPT_VERSION;
      $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
      $aResponse['query_ms'] = $ElapsedMs;

      if (is_numeric($aParams['player_id'])) {
        RedisClient::SetKey(RedisClient::INDEXER_PLAYER_PREFIX.$aParams['player_id'].$oWorld->grep_id.$oUser->id, json_encode($aResponse), 300);
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this player in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetAllianceGET()
  {
    $aParams = array();
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'alliance_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // get world
      $oWorld = World::getWorldById($aParams['world']);

      if (is_numeric($aParams['alliance_id'])) {
        $CachedResponse = RedisClient::GetKey(RedisClient::INDEXER_ALLIANCE_PREFIX.$aParams['alliance_id'].$oWorld->grep_id.$oUser->id);
        if ($CachedResponse!=false) {
          $aResponse = json_decode($CachedResponse, true);
          $aResponse['cached_response'] = true;
          return self::OutputJson($aResponse);
        }
      }

      // Get intel
      $Start = round(microtime(true) * 1000);
      $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForAlliance($oUser, $oWorld->grep_id, $aParams['alliance_id'], true);
      $ElapsedMs = round(microtime(true) * 1000) - $Start;
      if ($aIntel === null || sizeof($aIntel) <= 0) throw new ModelNotFoundException();

      // If model is too big, only select latest intel for each town
      if (sizeof($aIntel) > 1200) {
        $aCitiesSubset = array();
        $aIds = array();
        /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
        foreach ($aIntel as $oCity) {
          if (!in_array($oCity->town_id, $aIds)) {
            $aIds[] = $oCity->town_id;
            $aCitiesSubset[] = $oCity;
          }
        }
        $aIntel = $aCitiesSubset;
      }

      $aResponse = self::FormatBrowseOutput($aIntel, $oWorld);
      $aResponse['script_version'] = USERSCRIPT_VERSION;
      $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
      $aResponse['query_ms'] = $ElapsedMs;

      if (is_numeric($aParams['alliance_id'])) {
        RedisClient::SetKey(RedisClient::INDEXER_ALLIANCE_PREFIX.$aParams['alliance_id'].$oWorld->grep_id.$oUser->id, json_encode($aResponse), 300);
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this alliance in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * @deprecated Only used when parsed_date is unknown, but that field can no longer be NULL so this should never be called
   * @param $DateString
   * @param $Locale
   * @return string
   */
  private static function FormatReportDate($DateString, $Locale)
  {
    $Format = 'd-m-y H:i:s';
    switch ($Locale) {
      case 'Europe/Berlin':
        $Format = 'd-m-y H:i:s';
        break;
      case 'Europe/Amsterdam':
        $Format = 'd-m-y H:i:s';
        break;
      case 'Europe/London':
        if (strpos($DateString, '-') > 2) {
          $Format = 'Y-m-d H:i:s';
        } else if (substr($DateString, 5, 3) == '-18' && substr($DateString, 0, 2) != '18') {
          $Format = 'd-m-y H:i:s';
        } else if (substr($DateString, 0, 2) == '18') {
          $Format = 'y-m-d H:i:s';
        } else {
          $Format = 'y-m-d H:i:s';
        }
        break;
      default:
        $Format = 'd-m-y H:i:s';
        break;
    }

    // Convert
    $Date = Carbon::createFromFormat($Format, $DateString, new \DateTimeZone($Locale));
    $OutputFormat = 'd-m-y H:i:s';
    return $Date->format($OutputFormat);
  }

  /**
   * @param $aCities
   * @param \Grepodata\Library\Model\World $oWorld
   * @return array
   */
  private static function FormatBrowseOutput($aCities, $oWorld)
  {
    $aResponse = array(
      'info'=>array(),
      'cities'=>array('players'=>array()),
      'fire'=>array(),
      'myth'=>array(),
      'off'=>array(),
      'bir'=>array(),
      'def'=>array(),
      'trir'=>array(),
      'teams'=>array(),
    );
    $aFireshipCities = array();
    $aMythCities = array();
    $aBirCities = array();
    $aTrirCities = array();
    $aOffCities = array();
    $aDefCities = array();
    $aAllCities = array();

    /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
    $bHasIntel = false;
    $oNow = Carbon::now();
    $aDuplicateCheck = array();
    foreach ($aCities as $oCity) {
      if ($oCity->soft_deleted != null) {
        $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
        if ($oNow->diffInHours($oSoftDeleted) > 24) {
          // Ignore deleted records
          continue;
        }
      }

      if ($oCity->is_previous_owner_intel === 1) {
        // Ignore intel of previous owners
        continue;
      }

      $bHasIntel = true;
      $aCity = $oCity->getPublicFields();

      // Avoid duplicates (same records can be joined from multiple V1 indexes)
      $citystring = "_".$oCity->town_id.$oCity->parsed_date;
      $cityhash = md5($citystring);
      if (!in_array($cityhash, $aDuplicateCheck)) {
        $aDuplicateCheck[] = $cityhash;
      } else {
        // Skip same record coming from other V1 index
        continue;
      }

      $bPrimaryIntel = true;

      // Expand info
      $aResponse['info']['player_name'] = $oCity->player_name;
      $aResponse['info']['player_id'] = $oCity->player_id;
      $aResponse['info']['alliance_id'] = $oCity->alliance_id;

      // add teams to set
      if ($oCity->shared_via_indexes!=null) {
        $aTeams = explode(', ', $oCity->shared_via_indexes);
        foreach ($aTeams as $Team) {
          if (!in_array($Team, $aResponse['teams'])) {
            $aResponse['teams'][] = $Team;
          }
        }
      }

      // Fix date format for server format
      $oDate = null;
      $DaysAgo = 0;
      if (isset($aCity['parsed_date']) && $aCity['parsed_date'] != null) {
        try {
          $oDate = Carbon::parse($aCity['parsed_date']);
          $aCity['date'] = $oDate->format('d-m-y H:i:s');
          $DaysAgo = $oDate->diff($oNow)->days;
        } catch (Exception $e) {
          $aCity['date'] = self::FormatReportDate($aCity['date'], $oWorld->php_timezone);
        }
      } else {
        $aCity['date'] = self::FormatReportDate($aCity['date'], $oWorld->php_timezone);
      }

      // All
      if (!isset($aAllCities[$oCity->player_id])) {
        $aAllCities[$oCity->player_id] = array(
          'id'    => $oCity->player_id,
          'name'  => $oCity->player_name,
          'towns' => array(),
        );
      }
      if (!isset($aAllCities[$oCity->player_id]['towns'][$oCity->town_id])) {
        $aAllCities[$oCity->player_id]['towns'][$oCity->town_id] = array(
          'id'        => $oCity->id,
          'deleted'   => $aCity['deleted']||false,
          'name'      => $oCity->town_name,
          'town_id'   => $oCity->town_id,
          'player_id' => $oCity->player_id,
          'alliance_id' => $oCity->alliance_id,
          'all' => array()
        );
      }
      $aAllCities[$oCity->player_id]['towns'][$oCity->town_id]['all'][] = $oCity->getMinimalFields();

      // Myths
      if (isset($aCity['air']) && $aCity['air'] !== null && is_array($aCity['air']) && sizeof($aCity['air'])>0) {
        $bHasMyth = false;
        $aMythCity = array(
          'id'        => $oCity->id,
          'deleted'   => $aCity['deleted']||false,
          'csa_prio'  => $bPrimaryIntel,
          'cost'      => -1000,
          'town_id'   => $aCity['town_id'],
          'town_name' => $aCity['town_name'],
          'date'      => $aCity['date'],
          'units'     => array(),
        );
        foreach ($aCity['air'] as $Type => $Myth) {
          if ($Myth !== "") {
            if (in_array($Type, array_keys(self::translateMythType))) {
              $Type = self::translateMythType[$Type];
            }
            $aUnit = self::ParseValue($Type, $Myth);
            if (isset($aUnit['count']) && $aUnit['count'] > 3) {
              $bHasMyth = true;
              $cost = $aUnit['count'] - $DaysAgo*2.5 + $aUnit['killed'];
              $aMythCity['units'][] = $aUnit;
              $aMythCity['cost'] = max($aMythCity['cost'], $cost); // 100-20daysago = 60, 90-0daysago = 90
            }
          }
        }
        if ($bHasMyth) {
          if (!isset($aMythCities[$oCity->player_id])) {
            $aMythCities[$oCity->player_id] = array(
              'id'    => $oCity->player_id,
              'name'  => $oCity->player_name,
              'towns' => array(),
            );
          }
          $aMythCities[$oCity->player_id]['towns'][] = $aMythCity;
          $bPrimaryIntel = false;
        }
      }

      // land units
      if (isset($aCity['land']) && $aCity['land'] !== null && is_array($aCity['land']) && sizeof($aCity['land'])>0) {
        $aLandUnits = $aCity['land'];

        // OFF
        $Units = array();
        $maxCost = -1000;
        foreach (self::aOffKeys as $ProperName => $Unit) {
          foreach ($Unit['keys'] as $Key) {
            if (isset($aLandUnits[$Key])
              && $aLandUnits[$Key] !== null
              && $aLandUnits[$Key] !== ''
              && (!isset($Unit['type']) || in_array($oCity->report_type, $Unit['type']))
            ) {
              $aUnit = self::ParseValue($ProperName, $aLandUnits[$Key]);
              if (self::isValidNum($aUnit['count'], $Unit['min'])) {
                $cost = $aUnit['count'] - $DaysAgo*10;
                $maxCost = max($maxCost, $cost);
                $Units[] = $aUnit;
              }
            }
          }
        }

        if (sizeof($Units) > 0) {
          $aLandCity = array(
            'id'        => $oCity->id,
            'deleted'   => $aCity['deleted']||false,
            'csa_prio'  => $bPrimaryIntel,
            'cost'      => $maxCost,
            'town_id'   => $aCity['town_id'],
            'town_name' => $aCity['town_name'],
            'date'      => $aCity['date'],
            'units'     => $Units,
          );
          if (!isset($aOffCities[$oCity->player_id])) {
            $aOffCities[$oCity->player_id] = array(
              'id'    => $oCity->player_id,
              'name'  => $oCity->player_name,
              'towns' => array(),
            );
          }
          $aOffCities[$oCity->player_id]['towns'][] = $aLandCity;
          $bPrimaryIntel = false;
        }

        // DEF
        $Units = array();
        $maxCost = -1000;
        foreach (self::aDefKeys as $ProperName => $Unit) {
          foreach ($Unit['keys'] as $Key) {
            if (isset($aLandUnits[$Key])
              && $aLandUnits[$Key] !== null
              && $aLandUnits[$Key] !== ''
              && (!isset($Unit['type']) || in_array($oCity->report_type, $Unit['type']))
            ) {
              $aUnit = self::ParseValue($ProperName, $aLandUnits[$Key]);
              if (self::isValidNum($aUnit['count'], $Unit['min'], $Unit['max'])) {
                $cost = $aUnit['count'] - $DaysAgo*10;
                $maxCost = max($maxCost, $cost);
                $Units[] = $aUnit;
              }
            }
          }
        }

        if (sizeof($Units) > 0) {
          $aLandDefCity = array(
            'id'        => $oCity->id,
            'deleted'   => $aCity['deleted']||false,
            'csa_prio'  => $bPrimaryIntel,
            'cost'      => $maxCost,
            'town_id'   => $aCity['town_id'],
            'town_name' => $aCity['town_name'],
            'date'      => $aCity['date'],
            'units'     => $Units,
          );
          if (!isset($aDefCities[$oCity->player_id])) {
            $aDefCities[$oCity->player_id] = array(
              'id' => $oCity->player_id,
              'name' => $oCity->player_name,
              'towns' => array(),
            );
          }
          $aDefCities[$oCity->player_id]['towns'][] = $aLandDefCity;
          $bPrimaryIntel = false;
        }
      }

      // Fireships
      if (isset($aCity['fireships']) && $aCity['fireships'] !== null && $aCity['fireships'] !== '') {
        $num = self::parseDeathUnits($aCity['fireships']);
        if (self::isValidNum($num, 21)) {
          if (!isset($aFireshipCities[$oCity->player_id])) {
            $aFireshipCities[$oCity->player_id] = array(
              'name'  => $oCity->player_name,
              'id'    => $oCity->player_id,
              'towns' => array(),
            );
          }
          $aFireshipCities[$oCity->player_id]['towns'][] = array(
            'id'        => $oCity->id,
            'deleted'   => $aCity['deleted']||false,
            'csa_prio'  => $bPrimaryIntel,
            'cost'      => $num - $DaysAgo*4, // after 30 days, 300vs-30daysago(=300-90) will have a higher priority than 200vs-0daysago
            'town_id'   => $aCity['town_id'],
            'town_name' => $aCity['town_name'],
            'date'      => $aCity['date'],
            'units'     => $aCity['fireships'],
            'count'     => (strpos($aCity['fireships'],'(')!==false?substr($aCity['fireships'], 0, strpos($aCity['fireships'],'(')):$aCity['fireships'])
          );
          $bPrimaryIntel = false;
        }
      }

      // sea units
      if (isset($aCity['sea']) && $aCity['sea'] !== null && is_array($aCity['sea']) && sizeof($aCity['sea'])>0) {
        $aSeaUnits = (array) $aCity['sea'];

        // Bir
        $BirKey = '';
        if (isset($aSeaUnits['bir']) && $aSeaUnits['bir'] !== null && $aSeaUnits['bir'] !== '') {
          $BirKey = 'bir';
        } elseif (isset($aSeaUnits['bireme']) && $aSeaUnits['bireme'] !== null && $aSeaUnits['bireme'] !== '') {
          $BirKey = 'bireme';
        }
        if ($BirKey !== '') {
          $num = self::parseDeathUnits($aSeaUnits[$BirKey]);
          if (self::isValidNum($num, 10, 600)) {
            $aBirCity = array(
              'id'        => $oCity->id,
              'deleted'   => $aCity['deleted']||false,
              'csa_prio'  => $bPrimaryIntel,
              'cost'      => $num - $DaysAgo*4, // 300-30daysago(=300-90=210), 200-0daysago(=200)
              'town_id'   => $aCity['town_id'],
              'town_name' => $aCity['town_name'],
              'date'      => $aCity['date'],
              'units'     => $aSeaUnits[$BirKey],
              'count'     => (strpos($aSeaUnits[$BirKey],'(')!==false?substr($aSeaUnits[$BirKey], 0, strpos($aSeaUnits[$BirKey],'(')):$aSeaUnits[$BirKey])

            );
            if (!isset($aBirCities[$oCity->player_id])) {
              $aBirCities[$oCity->player_id] = array(
                'id'    => $oCity->player_id,
                'name'  => $oCity->player_name,
                'towns' => array(),
              );
            }
            $aBirCities[$oCity->player_id]['towns'][] = $aBirCity;
            $bPrimaryIntel = false;
          }
        }

        // Trir
        $TrirKey = '';
        if (isset($aSeaUnits['trir']) && $aSeaUnits['trir'] !== null && $aSeaUnits['trir'] !== '') {
          $TrirKey = 'trir';
        } elseif (isset($aSeaUnits['trireme']) && $aSeaUnits['trireme'] !== null && $aSeaUnits['trireme'] !== '') {
          $TrirKey = 'trireme';
        }
        if ($TrirKey !== '') {
          $num = self::parseDeathUnits($aSeaUnits[$TrirKey]);
          if (self::isValidNum($num, 10, 600)) {
            $aTrirCity = array(
              'id'        => $oCity->id,
              'deleted'   => $aCity['deleted']||false,
              'csa_prio'  => $bPrimaryIntel,
              'cost'      => $num - $DaysAgo*4, // 300-30daysago(=300-90=210), 200-0daysago(=200)
              'town_id'   => $aCity['town_id'],
              'town_name' => $aCity['town_name'],
              'date'      => $aCity['date'],
              'units'     => $aSeaUnits[$TrirKey],
              'count'     => (strpos($aSeaUnits[$TrirKey],'(')!==false?substr($aSeaUnits[$TrirKey], 0, strpos($aSeaUnits[$TrirKey],'(')):$aSeaUnits[$TrirKey])

            );
            if (!isset($aTrirCities[$oCity->player_id])) {
              $aTrirCities[$oCity->player_id] = array(
                'id'    => $oCity->player_id,
                'name'  => $oCity->player_name,
                'towns' => array(),
              );
            }
            $aTrirCities[$oCity->player_id]['towns'][] = $aTrirCity;
            $bPrimaryIntel = false;
          }
        }
      }

    }

    if ($bHasIntel === false) {
      throw new ModelNotFoundException("All intel was deleted");
    }

    // Sort and crop
    foreach ($aAllCities as $PlayerId => $aPlayer) {
      foreach ($aPlayer['towns'] as $CityId => $aCity) {
        $aAllCities[$PlayerId]['towns'][$CityId]['count'] = sizeof($aCity['all']);
        unset($aAllCities[$PlayerId]['towns'][$CityId]['all']);
      }
      usort($aAllCities[$PlayerId]['towns'], function ($a, $b) {
        if ($a['name'] == $b['name']) {
          return 0;
        }
        return ($a['name'] < $b['name']) ? -1 : 1;
      });
    }
    $aResponse['cities']['players'] = $aAllCities;

    $aCollection = array(
      'fire' => $aFireshipCities, 'myth' => $aMythCities, 'bir' => $aBirCities, 'trir' => $aTrirCities,
      'off' => $aOffCities, 'def' => $aDefCities
    );
    foreach ($aCollection as $Type => $aCities) {
      foreach ($aCities as $PlayerId => $aPlayer) {
        usort($aCities[$PlayerId]['towns'], function ($a, $b) {
          if ($a['town_name'] == $b['town_name']) {
            return ($a['cost'] < $b['cost']) ? 1 : -1;
          }
          return ($a['town_name'] < $b['town_name']) ? -1 : 1;
        });
        $lastId = 0;
        $bHasLowPriority = false;
        foreach ($aCities[$PlayerId]['towns'] as $id => $aCity) {
          if ($aCity['town_id'] != $lastId) {
            $aCities[$PlayerId]['towns'][$id]['priority'] = true;
          } else {
            $aCities[$PlayerId]['towns'][$id]['priority'] = false;
            $bHasLowPriority = true;
          }
          $lastId = $aCity['town_id'];
        }
        $aCities[$PlayerId]['contains_duplicates'] = $bHasLowPriority;
      }
      $aResponse[$Type]['players'] = $aCities;
    }

    // try to expand teams
    $aTeams = $aResponse['teams'];
    $aParsedTeams = array();
    foreach ($aTeams as $Team) {
      try {
        $oIndex = IndexInfo::where('key_code', '=', $Team)->firstOrFail();
        $aParsedTeams[] = array(
          'index_key' => $oIndex->key_code,
          'index_name' => $oIndex->index_name,
        );
      } catch (Exception $e) {}
    }
    $aResponse['teams'] = $aParsedTeams;

    // try to expand alliance
    $aResponse['info']['alliance_name'] = '';
    try {
      if (isset($aResponse['info']['alliance_id'])) {
        $oAlliance = Alliance::firstOrFail($aResponse['info']['alliance_id'], $oWorld->grep_id);
        $aResponse['info']['alliance_name'] = $oAlliance->name;
      }
    } catch (Exception $e) {}

    return $aResponse;
  }

  private static function parseDeathUnits($String)
  {
    try {
      $num = $String;
      if (strpos($num, '(') !== false) {
        $num = substr($num, 0, strpos($num, '('));
      }
      return $num;
    } catch (Exception $e) {
      return 0;
    }
  }

  private static function isValidNum($Num, $Min, $Max=0)
  {
    try {
      if ($Num <= $Min) {
        return false;
      }
      if ($Max > 0) {
        if ($Num >= $Max) {
          return false;
        }
      }
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  private static function ParseValue($Name, $Value) {
    if ($Value === "?") {
      return array(
        'name' => $Name,
        'count' => "?",
        'killed' => 0,
      );
    }
    $Count = 0;
    $Killed = 0;
    if (strpos($Value,'(-') !== FALSE) {
      $Count = substr($Value, 0, strpos($Value,'(-'));
      $Killed = substr($Value, strpos($Value,'(-')+2);
      $Killed = substr($Killed, 0, strpos($Value,')')-1);
    } else {
      $Count = $Value;
    }
    return array(
      'name' => $Name,
      'count' => (int) $Count,
      'killed' => (int) $Killed,
    );
  }
}
