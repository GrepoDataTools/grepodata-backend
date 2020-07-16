<?php

namespace Grepodata\Application\API\Route\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Model\Indexer\City;
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
      // Validate params
      $aParams = self::validateParams(array('key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // World
      $oWorld = World::getWorldById($oIndex->world);

      // Hide owner intel
      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);
      $oPlayer = Player::first($aParams['id'], $oIndex->world);
      if ($oPlayer !== null && in_array($oPlayer->alliance_id, $aOwners)) throw new ModelNotFoundException();

      // Find cities
      $aCities = CityInfo::allByPlayerId($aParams['key'], $aParams['id']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new ModelNotFoundException();

      $aResponse = self::FormatBrowseOutput($aCities, $oWorld);
      $aResponse['script_version'] = $oIndex->script_version;
      $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
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
      // Validate params
      $aParams = self::validateParams(array('key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // World
      $oWorld = World::getWorldById($oIndex->world);

      // Hide owner intel
      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);
      if (in_array($aParams['id'], $aOwners)) throw new ModelNotFoundException();

      // Find alliance cities
      $aCities = CityInfo::allByAllianceId($aParams['key'], $aParams['id']);

      if ($aCities === null || sizeof($aCities) <= 0) throw new ModelNotFoundException();

      // If model is too big, only select latest intel for each town
      if (sizeof($aCities) > 1200) {
        $aCitiesSubset = array();
        $aIds = array();
        /** @var City $oCity */
        foreach ($aCities as $oCity) {
          if (!in_array($oCity->town_id, $aIds)) {
            $aIds[] = $oCity->town_id;
            $aCitiesSubset[] = $oCity;
          }
        }
        $aCities = $aCitiesSubset;
      }

      $aResponse = self::FormatBrowseOutput($aCities, $oWorld);
      $aResponse['script_version'] = $oIndex->script_version;
      $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this alliance in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // World
      /** @var \Grepodata\Library\Model\World $oWorld */
      $oWorld = World::getWorldById($oIndex->world);

      // Find cities
      $aCities = CityInfo::allByTownId($aParams['key'], $aParams['id']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new ModelNotFoundException();

      $oNow = Carbon::now();
      $bHasIntel = false;
      $aResponse = array();
      /** @var City $oCity */
      foreach ($aCities as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        if (empty($aResponse)) {
          $aResponse['name'] = $oCity->town_name;
          $aResponse['player_id'] = $oCity->player_id;
          $aResponse['player_name'] = $oCity->player_name;
          $aResponse['alliance_id'] = $oCity->alliance_id;
          $aResponse['all'] = array();
          $aResponse['buildings'] = array();
        }

        $aCityFields = $oCity->getMinimalFields();
        if (isset($aCityFields['parsed_date']) && $aCityFields['parsed_date'] != null) {
          try {
            $aCityFields['date'] = Carbon::parse($aCityFields['parsed_date'])
              ->format('d-m-y H:i:s');
          } catch (Exception $e) {
            $aCityFields['date'] = self::FormatReportDate($aCityFields['date'], $oWorld->php_timezone);
          }
        } else {
          $aCityFields['date'] = self::FormatReportDate($aCityFields['date'], $oWorld->php_timezone);
        }

        if ($oCity->report_type != "attack_on_conquest") {
          $Build = $oCity->buildings;
          if ($Build != null && $Build != "" && $Build != "[]") {
            $aBuild = (array) json_decode($Build);
            if (is_array($aBuild) && sizeof($aBuild) > 0) {
              foreach ($aBuild as $Name => $Value) {
                $aResponse['buildings'][$Name] = array(
                  "level" => $Value,
                  "date" => $oCity = $aCityFields['date']
                );
              }
            }
          }
        }

        $aResponse['all'][] = $aCityFields;
      }

      if ($bHasIntel === false) {
        throw new ModelNotFoundException("All intel was deleted");
      }

      $aResponse['all'] = array_reverse($aResponse['all']);
      $aResponse['latest_version'] = $oIndex->script_version;
      $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
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
      'cities'=>array('players'=>array()),
      'fire'=>array(),
      'myth'=>array(),
      'off'=>array(),
      'bir'=>array(),
      'def'=>array(),
      'trir'=>array(),
    );
    $aFireshipCities = array();
    $aMythCities = array();
    $aBirCities = array();
    $aTrirCities = array();
    $aOffCities = array();
    $aDefCities = array();
    $aAllCities = array();

    /** @var City $oCity */
    $bHasIntel = false;
    $oNow = Carbon::now();
    foreach ($aCities as $oCity) {
      if ($oCity->soft_deleted != null) {
        $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
        if ($oNow->diffInHours($oSoftDeleted) > 24) {
          continue;
        }
      }
      $bHasIntel = true;
      $aCity = $oCity->getPublicFields();

      $bPrimaryIntel = true;

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