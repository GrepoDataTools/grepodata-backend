<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Database\Capsule\Manager as DB;

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
      ->orderBy('parsed_date', 'desc')
      ->get();
  }

  /**
   * @param $oUser
   * @param $ConquestId
   * @param bool $bCheckHiddenOwners
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForConquest($oUser, $ConquestId, $bCheckHiddenOwners = true)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners, false)
      ->where('Indexer_intel.conquest_id', '=', $ConquestId, 'and')
      ->groupBy('Indexer_intel.id')
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

//  /**
//   * @param User $oUser
//   * @param $Query string search query
//   * @param $World
//   * @return Collection
//   */
//  public static function searchPlayer(User $oUser, $Query, $World = null)
//  {
//    $oQuery = self::selectByUser($oUser)
//      ->where('Indexer_intel.player_name', 'LIKE', '%'.$Query.'%');
//
//    if (!empty($World)) {
//      $oQuery->where('Indexer_intel.world', '=', $World);
//    }
//
//    return $oQuery->groupBy('Indexer_intel.id')
//      ->get();
//  }

//  /**
//   * @param User $oUser
//   * @param $Query string search query
//   * @param $World
//   * @return Collection
//   */
//  public static function searchTown(User $oUser, $Query, $World = null)
//  {
//    $oQuery = self::selectByUser($oUser)
//      ->where('Indexer_intel.town_name', 'LIKE', '%'.$Query.'%');
//
//    if (!empty($World)) {
//      $oQuery->where('Indexer_intel.world', '=', $World);
//    }
//
//    return $oQuery->groupBy('Indexer_intel.id')
//      ->get();
//  }

  /**
   * @param \Grepodata\Library\Model\IndexV2\Intel $oIntel
   * @param World $oWorld
   * @param $aBuildings
   * @return array
   */
  public static function formatAsTownIntel($oIntel, $oWorld, &$aBuildings)
  {
    $aCityFields = array(
      'id'          => $oIntel->id,
      'date'        => $oIntel->report_date,
      'type'        => $oIntel->report_type,
      'hero'        => $oIntel->hero,
      'god'         => $oIntel->god,
      'silver'      => $oIntel->silver,
      'fireships'   => $oIntel->fireships,
      'parsed_date' => $oIntel->parsed_date,
      'buildings'   => json_decode($oIntel->buildings, true) ?? array(),
      'land'        => json_decode($oIntel->land_units, true) ?? array(),
      'sea'         => json_decode($oIntel->sea_units, true) ?? array(),
      'air'         => json_decode($oIntel->mythical_units, true) ?? array(),
      'deleted'     => ($oIntel->soft_deleted!=null?true:false)
    );
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
    $UnknownLand = null;
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
      if ($Name === 'unknown') {
        $UnknownLand = $aUnit;
      } else {
        $aUnits[] = $aUnit;
      }
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
    if (!is_null($UnknownLand)) {
      $aUnits[] = $UnknownLand;
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
      'conquest_id' => $oIntel->conquest_id > 0 ? $oIntel->conquest_id : 0,
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
        'killed' => "?",
      );
    }
    $Count = 0;
    $Killed = 0;
    if (strpos($Value,'(-') !== FALSE) {
      $Count = substr($Value, 0, strpos($Value,'(-'));
      $Killed = substr($Value, strpos($Value,'(-')+2);
      $Killed = substr($Killed, 0, strpos($Killed,')'));
    } else {
      $Count = $Value;
    }
    return array(
      'name' => $Name,
      'count' => $Name==='unknown'||$Name==='unknown_naval'?'?':((int) $Count),
      'killed' => $Killed==='?'?'?':(int) $Killed,
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
   * @param \Grepodata\Library\Model\IndexV2\Intel $oCity
   * @return array
   */
  public static function getMergedUnits(\Grepodata\Library\Model\IndexV2\Intel $oCity)
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
   * Get all intel for a given world
   * @param World $oWorld
   * @return \Grepodata\Library\Model\IndexV2\Intel[]
   */
  public static function allByWorld(World $oWorld)
  {
    return \Grepodata\Library\Model\IndexV2\Intel::where('world', '=', $oWorld->grep_id)->get();
  }

  /**
   * Get all intel that was indexed by a specific user
   * @param User $oUser
   * @param int $From
   * @param int $Size
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUser(User $oUser, $From = 0, $Size = 20)
  {
//    $query = \Grepodata\Library\Model\IndexV2\Intel::select(['Indexer_intel.*'])
//      ->join('Indexer_intel_shared', 'Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id')
//      ->where('Indexer_intel_shared.user_id', '=', $oUser->id)
//      ->orderBy('Indexer_intel.parsed_date', 'desc')
//      ->distinct('Indexer_intel.id');
//    $Total = $query->count();
//    $aIntel = $query->offset($From)->limit($Size+1)->get();

//    return \Grepodata\Library\Model\IndexV2\Intel::select(['Indexer_intel.*'])
//      ->join('Indexer_intel_shared', 'Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id')
//      ->where('Indexer_intel_shared.user_id', '=', $oUser->id)
//      ->orderBy('Indexer_intel.parsed_date', 'desc')
//      ->distinct('Indexer_intel.id')
//      ->offset($From)
//      ->limit($Size+1)
//      ->get();

    // Query to expand initial result with list of indexes that the intel was shared with
    $query = \Grepodata\Library\Model\IndexV2\IntelShared::select([
        'Indexer_intel.*',
        DB::raw("group_concat(Indexer_intel_shared.index_key SEPARATOR ', ') as shared_via_indexes")
      ])
      ->join(DB::raw('(
        SELECT Indexer_intel.*
        FROM Indexer_intel
        JOIN Indexer_intel_shared ON Indexer_intel_shared.intel_id = Indexer_intel.id
        WHERE Indexer_intel_shared.user_id = "' . $oUser->id . '"
      ) as Indexer_intel'),
      function($join)
      {
        $join->on('Indexer_intel_shared.intel_id', '=', 'Indexer_intel.id');
      });

    // If this is the first query, count the total rows
    $Total = null;
    if ($From==0) {
      $Total = $query->distinct('Indexer_intel.id')->count('Indexer_intel.id');
    }

    // Get the paginated records
    $aIntel = $query->groupBy('Indexer_intel.id')
      ->orderBy('Indexer_intel.id', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();

    return [$aIntel, $Total];
  }

  /**
   * Get all intel that the user has access to for a specific town
   * @param User $oUser
   * @param $World
   * @param $TownId
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForTown(User $oUser, $World, $TownId, $bCheckHiddenOwners=false)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners, true)
      ->where('Indexer_intel.town_id', '=', $TownId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->groupBy('Indexer_intel.id')
      ->get();
  }

  /**
   * Get all intel that the user has access to for a specific player
   * @param User $oUser
   * @param $World
   * @param $PlayerId
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForPlayer(User $oUser, $World, $PlayerId, $bCheckHiddenOwners=false)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners, true)
      ->where('Indexer_intel.player_id', '=', $PlayerId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->groupBy('Indexer_intel.id')
      ->get();
  }

  /**
   * Get all intel that the user has access to for a specific alliance
   * @param User $oUser
   * @param $World
   * @param $AllianceId
   * @param bool $bCheckHiddenOwners
   * @return \Grepodata\Library\Model\IndexV2\Intel[]|Builder[]|Collection
   */
  public static function allByUserForAlliance(User $oUser, $World, $AllianceId, $bCheckHiddenOwners=true)
  {
    return self::selectByUser($oUser, $bCheckHiddenOwners, true)
      ->where('Indexer_intel.alliance_id', '=', $AllianceId, 'and')
      ->where('Indexer_intel.world', '=', $World)
      ->orderBy('parsed_date', 'desc')
      ->groupBy('Indexer_intel.id')
      ->limit(10000)
      ->get();
  }

  /**
   * Select all intel for a specific user, this includes intel collected by the user but also intel shared with the user
   * @param User $oUser
   * @param bool $bCheckHiddenOwners If set to true, the query is joined on index owners to filter hidden records
   * @param bool $bAggregateSharedInfo If set to true, an aggregation is done on columns from the shared table
   * @return Builder
   */
  private static function selectByUser(User $oUser, $bCheckHiddenOwners=false, $bAggregateSharedInfo=false)
  {
    $Id = $oUser->id;

    $aSelectRange = \Grepodata\Library\Model\IndexV2\Intel::getMinimalSelect();
    if ($bAggregateSharedInfo) {
      // Aggregate list of indexes
      $aSelectRange[] = DB::raw("group_concat(Indexer_intel_shared.index_key SEPARATOR ', ') as shared_via_indexes");

      // Aggeragate list of indexers (= Not the same as Indexer_intel.indexed_by_user_id! multiple users can index the same report)
      $aSelectRange[] = DB::raw("group_concat(Indexer_intel_shared.user_id SEPARATOR ', ') as indexed_by_users");
    }

//    if ($bCheckHiddenOwners) {
//      // TODO: aggregate hidden status
//      // this way frontend can show a message if the intel is hidden and make a distinction between hidden or no intel
//      $aSelectRange[] = DB::raw("MIN(Indexer_owners_actual.hide_intel) as has_hidden_intel");
//      $aSelectRange[] = DB::raw("MAX(Indexer_owners_actual.hide_intel) as has_hidden_intel");
//    }

    // Select basic intel dimensions (joins on shared to get the index keys, left joins on roles to check user access)
    $oQuery = \Grepodata\Library\Model\IndexV2\Intel::select($aSelectRange)
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

  /**
   * This routes returns all intel for a specific town given a V1 index key
   * @param $Keys
   * @param $Id
   * @return mixed
   * @deprecated
   */
  public static function allByTownIdByV1IndexKeys($Keys, $Id)
  {
    return \Grepodata\Library\Model\IndexV2\Intel::whereIn('v1_index', $Keys, 'and')
      ->where('town_id', '=', $Id, 'and')
      ->whereNull('soft_deleted')
      ->orderBy('created_at', 'asc')
      ->get();
  }


}
