<?php

namespace Grepodata\Library\Controller\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class CityInfo
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
  const fireship   = 'attack_ship';

  /**
   * @param $Key string Index identifier
   * @param $Id int Town identifier
   * @return Collection
   */
  public static function allByTownId($Key, $Id)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('town_id', '=', $Id, 'and')
      ->whereNull('soft_deleted')
      ->orderBy('created_at', 'asc')
      ->get();
  }

  /**
   * @param $Key string Index identifier
   * @param $Name
   * @return Collection
   */
  public static function allByName($Key, $Name)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('town_name', 'LIKE', '%'.$Name.'%')
      ->orderBy('created_at', 'desc')
      ->get();
  }

  /**
   * @param $Key string Index identifier
   * @param $Id
   * @return City
   */
  public static function getById($Key, $Id)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Key string Index identifier
   * @param $Id int Player identifier
   * @return Collection
   */
  public static function allByPlayerId($Key, $Id)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('player_id', '=', $Id)
      ->orderBy('town_name', 'asc')
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param $Key string Index identifier
   * @param $Id int Alliance identifier
   * @return Collection
   */
  public static function allByAllianceId($Key, $Id)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('alliance_id', '=', $Id)
      ->orderBy('town_name', 'asc')
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param $Key string Index identifier
   * @return Collection
   */
  public static function allByKey($Key)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key)
      ->get();
  }

  /**
   * @param $Query string search query
   * @param $Key
   * @return Collection
   */
  public static function searchPlayer($Query, $Key)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('player_name', 'LIKE', '%'.$Query.'%')
      ->get();
  }

  /**
   * @param $Query string search query
   * @param $Key
   * @return Collection
   */
  public static function searchTown($Query, $Key)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('town_name', 'LIKE', '%'.$Query.'%')
      ->get();
  }

  /**
   * @param City $oCity
   * @param World $oWorld
   * @param $aBuildings
   * @return array
   */
  public static function formatAsTownIntel($oCity, $oWorld, &$aBuildings)
  {
    $aCityFields = $oCity->getMinimalFields();
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
      $aCityFields['sort_date'] = $oCity->created_at;
      $aCityFields['date'] = $aCityFields['sort_date']->format('d-m-y H:i:s');
    }

    $Wall = '';
    if ($oCity->report_type !== "attack_on_conquest") {
      $Build = $oCity->buildings;
      if ($Build != null && $Build != "" && $Build != "[]") {
        $aBuild = json_decode($Build, true);
        if (is_array($aBuild) && sizeof($aBuild) > 0) {
          foreach ($aBuild as $Name => $Value) {
            if ($Name === 'wall' && $Value !== '?') {
              $Wall = str_replace(' (-0)','',$Value);
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
      $aUnits[] = self::ParseValue($UnitName, $Value);
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
      $aUnits[] = self::ParseValue($UnitName, $Value);
    }
    if (isset($aCityFields['fireships']) && $aCityFields['fireships'] !== null && $aCityFields['fireships'] !== "") {
      $aUnits[] = self::ParseValue(self::fireship, $aCityFields['fireships']);
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
      $aUnits[] = self::ParseValue($UnitName, $Value);
    }

    $aFormatted = array(
      'id'        => $oCity->id,
      'deleted'   => $aCityFields['deleted'] || false,
      'sort_date' => $aCityFields['sort_date'],
      'date'    => $aCityFields['date'],
      'units'   => $aUnits,
      'type'    => $aCityFields['type'],
      'silver'  => $aCityFields['silver'],
      'wall'    => $Wall,
      'hero'    => strtolower($aCityFields['hero']),
      'god'     => $aCityFields['god'],
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
}