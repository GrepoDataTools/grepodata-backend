<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class Intel
{
  const land_units = array(
    'sword' => 'sword', 'sling' => 'slinger',
    'bow' => 'archer', 'spear' => 'hoplite',
    'caval' => 'rider', 'strijd' => 'chariot',
    'kata' => 'catapult', 'gezant' => 'godsent',
    'militia' => 'militia', 'unknown' => 'unknown');
  const myth_units = array(
    'sea_monster' => 'sea_monster', 'cyclope' => 'zyklop',
    'harp'      => 'harpy', 'medusa' => 'medusa',
    'mino'      => 'minotaur','manti' => 'manticore',
    'centaur'   => 'centaur', 'pegasus' => 'pegasus',
    'cerberus'  => 'cerberus', 'erinyes' => 'fury',
    'griff'     => 'griffin', 'boar' => 'calydonian_boar');
  const sea_units  = array(
    'slow_tp' => 'big_transporter', 'bir' => 'bireme',
    'attack_ship' => 'attack_ship', 'brander' => 'demolition_ship',
    'fast_tp' => 'small_transporter', 'trireme' => 'trireme',
    'kolo'    => 'colonize_ship', 'unknown_naval' => 'unknown_naval');
  const build = array(
    'senate' => 'main',
    'wood' => 'lumber',
    'farm' => 'farm',
    'stone' => 'stoner',
    'silver' => 'ironer',
    'baracks' => 'barracks',
    'temple' => 'temple',
    'storage' => 'storage',
    'trade' => 'market',
    'port' => 'docks',
    'academy' => 'academy',
    'wall' => 'wall',
    'cave' =>  'hide',
    'theater'=> 'theater',
    'badhuis' => 'thermal',
    'bibliotheek' => 'library',
    'vuurtoren' => 'lighthouse',
    'toren' => 'tower',
    'godenbeeld' => 'statue',
    'orakel' => 'oracle',
    'handelskantoor' => 'trade_office',
  );
  const fireship = 'attack_ship';
  const sea_monster = 'sea_monster';

  /**
   * @param IndexInfo $oIndex
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByIndex(IndexInfo $oIndex)
  {
    return self::selectByIndex($oIndex)
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param IndexInfo $oIndex
   * @return string
   */
  public static function maxVersion(IndexInfo $oIndex)
  {
    return self::selectByIndex($oIndex)->max('script_version');
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\IndexV2\Intel
   */
  public static function getById($Id)
  {
    return \Grepodata\Library\Model\IndexV2\Intel::where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param User $oUser
   * @param $Query string search query
   * @param $World
   * @return Collection
   */
  public static function searchPlayer(User $oUser, $Query, $World = null)
  {
    $oQuery = self::selectByUser($oUser)
      ->where('Indexer_intel.player_name', 'LIKE', '%'.$Query.'%');

    if (!empty($World)) {
      $oQuery->where('Indexer_intel.world', '=', $World);
    }

    return $oQuery->orderBy('id', 'desc')
      ->distinct('Indexer_intel.id')
      ->get();
  }

  /**
   * @param User $oUser
   * @param $Query string search query
   * @param $World
   * @return Collection
   */
  public static function searchTown(User $oUser, $Query, $World = null)
  {
    $oQuery = self::selectByUser($oUser)
      ->where('Indexer_intel.town_name', 'LIKE', '%'.$Query.'%');

    if (!empty($World)) {
      $oQuery->where('Indexer_intel.world', '=', $World);
    }

    return $oQuery->orderBy('id', 'desc')
      ->distinct('Indexer_intel.id')
      ->get();
  }

  /**
   * @param \Grepodata\Library\Model\IndexV2\Intel $oIntel
   * @param World $oWorld
   * @param $aBuildings
   * @return array
   */
  public static function formatAsTownIntel($oIntel, $oWorld, &$aBuildings)
  {
    $aCityFields = $oIntel->getMinimalFields();
    $aCityFields['sort_date'] = 0;
    try {
      if (isset($aCityFields['parsed_date']) && $aCityFields['parsed_date'] != null) {
        $aCityFields['sort_date'] = Carbon::parse($aCityFields['parsed_date']);
        $aCityFields['date'] = $aCityFields['sort_date']->format('d-m-y H:i:s');
      } else {
        $aCityFields['date'] = self::FormatReportDate($aCityFields['date'], $oWorld->php_timezone);
        $aCityFields['sort_date'] = Carbon::createFromFormat('d-m-y H:i:s', $aCityFields['date']);
      }
    } catch (Exception $e) {
      $aCityFields['sort_date'] = $oIntel->created_at;
      $aCityFields['date'] = $aCityFields['sort_date']->format('d-m-y H:i:s');
    }

    $DaysAgo = 0;
    try {
      $oNow = Carbon::now();
      $DaysAgo = $aCityFields['sort_date']->diff($oNow)->days;
    } catch (Exception $e) {}

    $Wall = '';
    $aStonehail = null;
    if ($oIntel->report_type !== "attack_on_conquest") {
      $Build = $oIntel->buildings;
      if ($Build != null && $Build != "" && $Build != "[]") {
        $aBuild = json_decode($Build, true);
        $NumBuildings = sizeof($aBuild);
        if (is_array($aBuild) && $NumBuildings > 0) {
          foreach ($aBuild as $Name => $Value) {
            if ($Name === 'wall' && $Value !== '?') {
              $Wall = str_replace(' (-0)','',$Value);
            } elseif ($NumBuildings <= 2) {
              // stonehail
              $aStonehail = array(
                'building' => $Name,
                'value' => $Value
              );
            }
            if ($Name === 'special_1' || $Name === 'special_2') {
              $Name = $Value;
              $Value = 1;
            }
            foreach (self::build as $old => $new) {
              if ($Name === $old) {
                $Name = $new;
              }
            }
            preg_match_all('/[0-9]{1,}/', $Value, $aBuildingMatches);
            if (!empty($aBuildingMatches) && isset($aBuildingMatches[0][0])) {
              $Value = (int) $aBuildingMatches[0][0];
              if (isset($aBuildingMatches[0][1])) {
                $Value -= (int) $aBuildingMatches[0][1];
              }
            }
            $aBuildings[$Name] = array(
              "level" => $Value,
              "date" => $aCityFields['date']
            );
          }
        }
      }
    }

    // build info list
    $aUnits = array();
    $UnitCost = 0;
    foreach ($aCityFields['land'] as $Name => $Value) {
      $UnitName = "";
      foreach (self::land_units as $ForumName => $FixedName) {
        if ($Name === $ForumName) {
          $UnitName = $FixedName;
          break;
        }
      }
      if ($UnitName === "") {
        $UnitName = $Name;
      }
      $aUnit = self::ParseValue($UnitName, $Value);
      if (isset($aUnit['count']) && $aUnit['count']!='?') {
        $UnitCost += $aUnit['count'];
      }
      $aUnits[] = $aUnit;
    }
    foreach ($aCityFields['air'] as $Name => $Value) {
      $UnitName = "";
      foreach (self::myth_units as $ForumName => $FixedName) {
        if ($Name === $ForumName) {
          $UnitName = $FixedName;
          break;
        }
      }
      if ($UnitName === "") {
        $UnitName = $Name;
      }
      $aUnit = self::ParseValue($UnitName, $Value);
      if (isset($aUnit['count']) && $aUnit['count']!='?') {
        $UnitCost += $aUnit['count'] * 4;
      }
      $aUnits[] = $aUnit;
    }
    if (isset($aCityFields['fireships']) && $aCityFields['fireships'] !== null && $aCityFields['fireships'] !== "") {
      $aUnit = self::ParseValue(self::fireship, $aCityFields['fireships']);
      if (isset($aUnit['count']) && $aUnit['count']!='?') {
        $UnitCost += $aUnit['count'] * 3;
      }
      $aUnits[] = $aUnit;
    }
    foreach ($aCityFields['sea'] as $Name => $Value) {
      $UnitName = "";
      foreach (self::sea_units as $ForumName => $FixedName) {
        if ($Name === $ForumName) {
          $UnitName = $FixedName;
          break;
        }
      }
      if ($UnitName === "") {
        $UnitName = $Name;
      }
      $aUnit = self::ParseValue($UnitName, $Value);
      if (isset($aUnit['count']) && $aUnit['count']!='?') {
        $UnitCost += $aUnit['count'] * 2;
      }
      $aUnits[] = $aUnit;
    }

    // Calc intel cost (higher is better)
    $IntelCost = $UnitCost * (1-(min(($DaysAgo+1)*5, 90)/100));
    if (in_array($aCityFields['type'], array('enemy_attack', 'wisdom', 'support'))) {
      $IntelCost *= 100; // We care a lot more about this information
    }

    // Silver
    $Silver = $aCityFields['silver'];
    preg_match_all('/[0-9]{3,}/', $Silver, $aSilverMatches);
    if (!empty($aSilverMatches) && isset($aSilverMatches[0][0])) {
      $Silver = (int) $aSilverMatches[0][0];
      if (isset($aSilverMatches[0][1])) {
        $Silver += (int) $aSilverMatches[0][1];
      }
    }

    // Format response
    $aFormatted = array(
      'id'        => $oIntel->id,
      'deleted'   => $aCityFields['deleted'] || false,
      'sort_date' => $aCityFields['sort_date'],
      'date'    => $aCityFields['date'],
      'units'   => $aUnits,
      'type'    => $aCityFields['type'],
      'silver'  => (string) $Silver,
      'wall'    => $Wall,
      'stonehail' => $aStonehail,
// TODO: conquest ids
//      'conquest_id' => $oIntel->conquest_id > 0 ? $oIntel->conquest_id : 0,
      'conquest_id' => 0,
      'hero'    => strtolower($aCityFields['hero']),
      'god'     => strtolower($aCityFields['god']),
      'cost'    => (int) $IntelCost
    );
    return $aFormatted;
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
   * Returns a merged array of all the units in the City record
   * @param City $oCity
   * @return array
   */
  public static function getMergedUnits(City $oCity)
  {
    $aUnits = array();
    if (!empty($oCity->mythical_units) && $oCity->mythical_units != "[]") {
      self::parseUnitField($aUnits, $oCity->mythical_units);
    }
    if (!empty($oCity->land_units) && $oCity->land_units != "[]") {
      self::parseUnitField($aUnits, $oCity->land_units);
    }
    if (!empty($oCity->sea_units) && $oCity->sea_units != "[]") {
      self::parseUnitField($aUnits, $oCity->sea_units);
    }
    if (!empty($oCity->fireships) && is_string($oCity->fireships) && strlen($oCity->fireships) > 0) {
      $aUnits[self::fireship] = $oCity->fireships;
    }
    return $aUnits;
  }

  /**
   * Split land and sea units and parse their values
   * @param $aUnits
   * @return array
   */
  public static function splitLandSeaUnits($aUnits)
  {
    $aSplitUnits = array(
      'sea' => array(),
      'land' => array(),
    );
    foreach ($aUnits as $Unit => $Value) {
      if ($Unit == self::fireship || $Unit == self::sea_monster || in_array($Unit, array_values(self::sea_units))) {
        $aSplitUnits['sea'][$Unit] = $Value;
      } else {
        $aSplitUnits['land'][$Unit] = $Value;
      }
    }
    $aSplitUnits['sea'] = self::parseUnitLossCount($aSplitUnits['sea']);
    $aSplitUnits['land'] = self::parseUnitLossCount($aSplitUnits['land']);
    return $aSplitUnits;
  }

  /**
   * Adds the units from the $InputField to the $aUnits array
   * translates unit names to proper keys
   * @param $aUnits
   * @param $InputField
   */
  public static function parseUnitField(&$aUnits, $InputField)
  {
    $aInputUnits = json_decode($InputField, true);
    foreach ($aInputUnits as $Key => $Value) {
      $Key = self::land_units[$Key] ?? $Key;
      $Key = self::sea_units[$Key] ?? $Key;
      $Key = self::myth_units[$Key] ?? $Key;
      $aUnits[$Key] = $Value;
    }
  }

  /**
   * Parses unit losses. e.g. slinger:400(-200) to [count: 400, loss: 200, name: slinger, raw: 400(-200)]
   * @param $aInputUnits
   * @return array
   */
  public static function parseUnitLossCount($aInputUnits)
  {
    $aParsed = array();
    foreach ($aInputUnits as $Key => $Value) {
      $Count = 0;
      $Killed = 0;
      preg_match_all('/[0-9]{1,}/', $Value, $aValueMatches);
      if (!empty($aValueMatches) && isset($aValueMatches[0][0])) {
        $Count = (int) $aValueMatches[0][0];
        if (isset($aValueMatches[0][1])) {
          $Killed = (int) $aValueMatches[0][1];
        }
      }
      $aParsed[] = array(
        'count' => $Count,
        'killed' => $Killed,
        'name' => $Key,
        'raw' => $Value,
      );
    }
    return $aParsed;
  }

  /**
   * @param User $oUser
   * @param int $From
   * @param int $Size
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUser(User $oUser, $From = 0, $Size = 20)
  {
    return \Grepodata\Library\Model\IndexV2\Intel::select(['Indexer_intel.*'])
      ->join('Indexer_intel_shared', 'Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id')
      ->where('Indexer_intel_shared.user_id', '=', $oUser->id)
      ->orderBy('Indexer_intel.id', 'desc')
      ->distinct('Indexer_intel.id')
      ->offset($From)
      ->limit($Size+1)
      ->get();
  }

  /**
   * @param User $oUser
   * @param $World
   * @param $TownId
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForTown(User $oUser, $World, $TownId, $bCheckHiddenOwners=false)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners)
      ->where('Indexer_intel.town_id', '=', $TownId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->orderBy('created_at', 'asc')
      ->distinct('Indexer_intel.id')
      ->get();
  }

  /**
   * @param User $oUser
   * @param $World
   * @param $PlayerId
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForPlayer(User $oUser, $World, $PlayerId, $bCheckHiddenOwners=false)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners)
      ->where('Indexer_intel.player_id', '=', $PlayerId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->orderBy('id', 'desc')
      ->distinct('Indexer_intel.id')
      ->get();
  }

  /**
   * @param User $oUser
   * @param $World
   * @param $AllianceId
   * @param bool $bCheckHiddenOwners
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForAlliance(User $oUser, $World, $AllianceId, $bCheckHiddenOwners=false)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners)
      ->where('Indexer_intel.alliance_id', '=', $AllianceId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->orderBy('id', 'desc')
      ->distinct('Indexer_intel.id')
      ->get();
  }

  /**
   * Select all intel for a specific user, this includes intel collected by the user but also intel shared with the user
   * @param User $oUser
   * @param bool $bCheckHiddenOwners
   * @return Builder
   */
  private static function selectByUser(User $oUser, $bCheckHiddenOwners=false)
  {
    $Id = $oUser->id;

    // Select basic intel dimensions (joins on shared to get the index keys, left joins on roles to check user access)
    $oQuery = \Grepodata\Library\Model\IndexV2\Intel::select(['Indexer_intel.*'])
      ->join('Indexer_intel_shared', 'Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id')
      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_intel_shared.index_key');

    if ($bCheckHiddenOwners===true) {
      // Left join index owners
      $oQuery->leftJoin('Indexer_owners_actual', function($query)
      {
        $query->on('Indexer_owners_actual.index_key', '=', 'Indexer_intel_shared.index_key')
          ->on('Indexer_owners_actual.alliance_id', '=', 'Indexer_intel.alliance_id');
      });

      // Keep records without owner data or if owner intel is not hidden
      $oQuery->where(function ($query) {
        $query->where('Indexer_owners_actual.hide_intel', '=', null)
          ->orWhere('Indexer_owners_actual.hide_intel', '=', false);
      });
    }

    // Filter records that belong to a specific user (can be either personal intel or shared intel)
    $oQuery->where(function ($query) use ($Id) {
        $query->where('Indexer_intel_shared.user_id', '=', $Id)
          ->orWhere('Indexer_roles.user_id', '=', $Id);
      });

    return $oQuery;

//    SELECT * FROM Indexer_intel
//    JOIN Indexer_intel_shared ON Indexer_intel.id = Indexer_intel_shared.intel_id
//    LEFT JOIN Indexer_roles ON Indexer_roles.index_key = Indexer_intel_shared.index_key
//    LEFT JOIN Indexer_owners_actual ON Indexer_owners_actual.index_key = Indexer_intel_shared.index_key AND Indexer_owners_actual.alliance_id = Indexer_intel.alliance_id
//    WHERE (Indexer_owners_actual.hide_intel IS NULL OR Indexer_owners_actual.hide_intel = 0)
//    AND (Indexer_intel_shared.user_id = 1 OR Indexer_roles.user_id = 1)
//    AND Indexer_intel.world = 'nl84' AND Indexer_intel.town_id = 1662

  }

  /**
   * Select all intel for a specific index
   * @param IndexInfo $oIndex
   * @return Builder
   */
  private static function selectByIndex(IndexInfo $oIndex)
  {
    return \Grepodata\Library\Model\IndexV2\Intel::select(['Indexer_intel.*'])
      ->join('Indexer_intel_shared', 'Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id')
      ->where('Indexer_intel_shared.index_key', '=', $oIndex->key_code);
  }
}
