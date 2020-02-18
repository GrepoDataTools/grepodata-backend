<?php

namespace Grepodata\Library\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Exception\ForumParserExceptionDebug;
use Grepodata\Library\Exception\ForumParserExceptionError;
use Grepodata\Library\Exception\ForumParserExceptionWarning;
use Grepodata\Library\Exception\InboxParserExceptionDebug;
use Grepodata\Library\Exception\InboxParserExceptionError;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ForumParser
{
  const format = array(
    'nl' => array(
      'day' => 'd-m-y',
      'day_regex' => '/[0-9]{2}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'de' => array(
      'day' => 'd.m.y',
      'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'cz' => array(
      'day' => 'd.m.y',
      'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'no' => array(
      'day' => 'd.m.y',
      'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'en' => array(
      'day' => 'Y-m-d',
      'day_regex' => '/[0-9]{4}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'fr' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'es' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'pt' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'hu' => array(
      'day' => 'y/m/d',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'it' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'us' => array(
      'day' => 'Y-m-d',
      'day_regex' => '/[0-9]{4}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'dk' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'ru' => array(
      'day' => 'd.m.y',
      'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'ar' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'br' => array(
      'day' => 'd/m/y',
      'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s',
      'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
  );
  const report_types = array(
    'spy'      => 'spy',
    'friendly' => 'friendly_attack',
    'enemy'    => 'enemy_attack',
    'conquest' => 'attack_on_conquest'
  );
  const aUnitNames = array(
    // Misc
    "unknown_naval" => array('type' => 'unknown_naval', 'value' => null, 'god' => null),
    "unknown"       => array('type' => 'unknown',       'value' => null, 'god' => null),

    // Regular units
    "unit_militia"  => array('type' => 'militia','value' => null, 'god' => null),
    "unit_sword"    => array('type' => 'sword',  'value' => null, 'god' => null),
    "unit_slinger"  => array('type' => 'sling',  'value' => null, 'god' => null),
    "unit_archer"   => array('type' => 'bow',    'value' => null, 'god' => null),
    "unit_hoplite"  => array('type' => 'spear',  'value' => null, 'god' => null),
    "unit_rider"    => array('type' => 'caval',  'value' => null, 'god' => null),
    "unit_chariot"  => array('type' => 'strijd', 'value' => null, 'god' => null),
    "unit_catapult" => array('type' => 'kata',   'value' => null, 'god' => null),
    "unit_godsent"  => array('type' => 'gezant', 'value' => null, 'god' => null),

    // Sea units
    "unit_big_transporter"    => array('type' => 'slow_tp',   'value' => null,    'god' => null),
    "unit_bireme"             => array('type' => 'bir',       'value' => null,    'god' => null),
    "unit_attack_ship"        => array('type' => 'fireship',  'value' => null,    'god' => null),
    "unit_demolition_ship"    => array('type' => 'brander',   'value' => null,    'god' => null),
    "unit_small_transporter"  => array('type' => 'fast_tp',   'value' => null,    'god' => null),
    "unit_trireme"            => array('type' => 'trireme',   'value' => null,    'god' => null),
    "unit_colonize_ship"      => array('type' => 'kolo',      'value' => null,    'god' => null),

    // Mythical units
    "unit_manticore"          => array('type' => 'manti',       'value' => null,  'god' => 'Zeus'),
    "unit_minotaur"           => array('type' => 'mino',        'value' => null,  'god' => 'Zeus'),
    "unit_zyklop"             => array('type' => 'cyclope',     'value' => null,  'god' => 'Poseidon'),
    "unit_sea_monster"        => array('type' => 'sea_monster', 'value' => null,  'god' => 'Poseidon'),
    "unit_harpy"              => array('type' => 'harp',        'value' => null,  'god' => 'Hera'),
    "unit_medusa"             => array('type' => 'medusa',      'value' => null,  'god' => 'Hera'),
    "unit_centaur"            => array('type' => 'centaur',     'value' => null,  'god' => 'Athene'),
    "unit_pegasus"            => array('type' => 'pegasus',     'value' => null,  'god' => 'Athene'),
    "unit_cerberus"           => array('type' => 'cerberus',    'value' => null,  'god' => 'Hades'),
    "unit_fury"               => array('type' => 'erinyes',     'value' => null,  'god' => 'Hades'),
    "unit_griffin"            => array('type' => 'griff',       'value' => null,  'god' => 'Artemis'),
    "unit_calydonian_boar"    => array('type' => 'boar',        'value' => null,  'god' => 'Artemis'),

    // Heros
    "unit_deimos"             => array('type' => 'hero',  'value' => 'Deimos',        'god' => null),
    "unit_ferkyon"            => array('type' => 'hero',  'value' => 'Ferkyon',       'god' => null),
    "unit_apheledes"          => array('type' => 'hero',  'value' => 'Apheledes',     'god' => null),
    "unit_agamemnon"          => array('type' => 'hero',  'value' => 'Agamemnon',     'god' => null),
    "unit_medea"              => array('type' => 'hero',  'value' => 'Medea',         'god' => null),
    "unit_atalanta"           => array('type' => 'hero',  'value' => 'Atalanta',      'god' => null),
    "unit_cheiron"            => array('type' => 'hero',  'value' => 'Cheiron',       'god' => null),
    "unit_christopholus"      => array('type' => 'hero',  'value' => 'Christopholus', 'god' => null),
    "unit_democritus"         => array('type' => 'hero',  'value' => 'Democritus',    'god' => null),
    "unit_hector"             => array('type' => 'hero',  'value' => 'Hector',        'god' => null),
    "unit_helena"             => array('type' => 'hero',  'value' => 'Helena',        'god' => null),
    "unit_herakles"           => array('type' => 'hero',  'value' => 'Herakles',      'god' => null),
    "unit_jason"              => array('type' => 'hero',  'value' => 'Jason',         'god' => null),
    "unit_leonidas"           => array('type' => 'hero',  'value' => 'Leonidas',      'god' => null),
    "unit_odysseus"           => array('type' => 'hero',  'value' => 'Odysseus',      'god' => null),
    "unit_orpheus"            => array('type' => 'hero',  'value' => 'Orpheus',       'god' => null),
    "unit_terylea"            => array('type' => 'hero',  'value' => 'Teryplea',      'god' => null),
    "unit_urephon"            => array('type' => 'hero',  'value' => 'Urephon',       'god' => null),
    "unit_zuretha"            => array('type' => 'hero',  'value' => 'Zuretha',       'god' => null),
    "unit_themistokles"       => array('type' => 'hero',  'value' => 'Themistokles',  'god' => null),
  );

  /**
   * @param $IndexKey
   * @param $aReportData
   * @param $ReportPoster
   * @param $Fingerprint
   * @param string $Locale
   * @return Mixed
   * @throws ForumParserExceptionDebug
   * @throws ForumParserExceptionError
   * @throws ForumParserExceptionWarning
   */
  public static function ParseReport($IndexKey, $aReportData, $ReportPoster, $Fingerprint, $Locale='nl')
  {
    try {
      $oIndexInfo = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($IndexKey);
      $aCityInfo = self::ExtractCityInfo($aReportData, $Fingerprint, $oIndexInfo, $Locale);

      $AllianceId = 0;
      if ($aCityInfo['player_name'] !== 'Ghost' && $aCityInfo['player_id'] !== 0) {
        $oPlayer = Player::firstById($oIndexInfo->world, $aCityInfo['player_id']);
        if ($oPlayer === null) {
          throw new ForumParserExceptionWarning("Unable to find player with name ".$aCityInfo['player_name']." and id ".$aCityInfo['player_id']);
        }
        $AllianceId = $oPlayer->alliance_id;
      }

      if (!isset($aCityInfo['city_id']) || is_null($aCityInfo['city_id']) || !isset($aCityInfo['city_name']) || is_null($aCityInfo['city_name'])) {
        throw new ForumParserExceptionWarning("Unable to find report subject city");
      }

      $aBuildingIds = array('senate', 'wood', 'farm', 'stone', 'silver', 'baracks', 'temple', 'storage', 'trade', 'port', 'academy', 'wall', 'cave', 'special_1', 'special_2');
      $aLandUnitIds = array('militia', 'sword', 'sling', 'bow', 'spear', 'caval', 'strijd', 'kata', 'gezant', 'unknown');
      $aMythUnitIds = array('mino', 'manti', 'sea_monster', 'harp', 'medusa', 'centaur', 'pegasus', 'cerberus', 'erinyes', 'cyclope', 'griff', 'boar');
      $aSeaUnitIds  = array('slow_tp', 'bir', 'brander', 'fast_tp', 'trireme', 'kolo', 'unknown_naval');

      $aBuildings = array();
      foreach ($aBuildingIds as $Building) {
        if (isset($aCityInfo[$Building])) $aBuildings[$Building] = $aCityInfo[$Building];
      }
      $aLandUnits = array();
      foreach ($aLandUnitIds as $LandUnit) {
        if (isset($aCityInfo[$LandUnit])) $aLandUnits[$LandUnit] = $aCityInfo[$LandUnit];
      }
      $aMythUnits = array();
      foreach ($aMythUnitIds as $MythUnit) {
        if (isset($aCityInfo[$MythUnit])) $aMythUnits[$MythUnit] = $aCityInfo[$MythUnit];
      }
      $aSeaUnits = array();
      foreach ($aSeaUnitIds as $SeaUnit) {
        if (isset($aCityInfo[$SeaUnit])) $aSeaUnits[$SeaUnit] = $aCityInfo[$SeaUnit];
      }
      $Fireships = '';
      if (isset($aCityInfo['fireship'])) $Fireships = $aCityInfo['fireship'];

      // Optional report poster details
      try {
        $oPosterPlayer = Player::firstByName($oIndexInfo->world, $ReportPoster);
      } catch (\Exception $e) {}

      $oCity = new City();
      $oCity->index_key   = $IndexKey;
      $oCity->town_id     = $aCityInfo['city_id'];
      $oCity->town_name   = $aCityInfo['city_name'];
      $oCity->player_id   = $aCityInfo['player_id'];
      $oCity->player_name = $aCityInfo['player_name'];
      $oCity->alliance_id = $AllianceId;
      if (isset($oPosterPlayer) && $oPosterPlayer !== null && $oPosterPlayer->alliance_id != $AllianceId) {
        $oCity->poster_player_id = $oPosterPlayer->grep_id;
        $oCity->poster_alliance_id = $oPosterPlayer->alliance_id;
      }
      if (isset($aCityInfo['conquest_id'])) {
        $oCity->conquest_id = $aCityInfo['conquest_id'];
      }
      $oCity->report_date = $aCityInfo['mutation_date'];
      if (isset($aCityInfo['parsed_date'])) {
        $oCity->parsed_date = $aCityInfo['parsed_date'];
      }
      $oCity->report_type = $aCityInfo['report_type'];
      $oCity->hero        = (isset($aCityInfo['hero'])?$aCityInfo['hero']:null);
      $oCity->god         = (isset($aCityInfo['god'])?$aCityInfo['god']:null);
      $oCity->silver      = (isset($aCityInfo['silver_in_cave'])?$aCityInfo['silver_in_cave']:null);
      $oCity->buildings   = json_encode($aBuildings);
      $oCity->land_units  = json_encode($aLandUnits);
      $oCity->mythical_units = json_encode($aMythUnits);
      $oCity->sea_units   = json_encode($aSeaUnits);
      $oCity->fireships   = $Fireships;
      $oCity->type        = 'forum';

      if ($oCity->save()) {
        return array(
          'id' => $oCity->id,
          'debug' => (isset($aCityInfo['print_debug']) && $aCityInfo['print_debug'] === true ? true : false)
        );
      } else {
        throw new ForumParserExceptionWarning("Unable to save City record: " . $oCity->toJson());
      }
    }
    catch(ForumParserExceptionDebug $e) {throw $e;}
    catch(ForumParserExceptionWarning $e) {throw $e;}
    catch(ForumParserExceptionError $e) {throw $e;}
    catch (\Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        throw new ForumParserExceptionDebug("Duplicate index city entry ignored.");
      } else {
        throw new ForumParserExceptionError("uncaught exception in forum parser: " . $e->getMessage() . '. [' . $e->getTraceAsString() . ']');
      }
    }

  }

  /**
   * @param $aReportData
   * @param $Fingerprint
   * @param string $Locale
   * @param IndexInfo $oIndex
   * @return array
   * @throws ForumParserExceptionWarning
   * @throws ForumParserExceptionError
   * @throws ForumParserExceptionDebug
   * @throws \Grepodata\Library\Exception\ParserDefaultWarning
   */
  private static function ExtractCityInfo($aReportData, $Fingerprint, IndexInfo $oIndex, $Locale='nl')
  {
    $cityInfo = array();

    // Check report
    $ReportClass = $aReportData['attributes']['class'];
    if (strpos($ReportClass, "published_report") === false) {
      throw new ForumParserExceptionError("Invalid report class. report data does not contain published_report");
    }

    // Navigate to report child (If report is contained within a bold, underline or italic BBcode element, then each of these will add a layer of abstraction)
    $LoopCount = 0;
    if (count($aReportData['content']) < 3 && isset($aReportData['content'][0]['type'])) {
      while (in_array($aReportData['content'][0]['type'], array('B', 'U', 'I'))) {
        $LoopCount += 1;
        if ($LoopCount >= 7) {
          throw new ForumParserExceptionError("Report data loop out of bounds");
        }

        $aReportData = $aReportData['content'][0];
      }
    }

    if (count($aReportData['content']) < 3) {
      throw new ForumParserExceptionError("Report content missing");
    }


    // Get date element
    $ReportDate = $aReportData['content'][3]['content'][5]['content'][0];

    // Parse date
    $bLocalFallback = false;
    if (!key_exists($Locale, self::format)) {
      // Use default locale as fallback: nl
      Logger::warning("ForumParser " . $Fingerprint . ": TODO add date format for locale " . $Locale . ". date example: " . $ReportDate);
      $Locale = 'nl';
      $bLocalFallback = true;
    }

    if ($ReportDate == null) {
      // Fallback to today
      Logger::warning("ForumParser " . $Fingerprint . ": unable to locate report date");
      $cityInfo['mutation_date'] = date(self::format[$Locale]['day'].' '.self::format[$Locale]['time']);
      $cityInfo['parsed_date'] = new Carbon();
      $cityInfo['print_debug'] = true;
    } else {
      if (!is_string($ReportDate) && is_array($ReportDate)) {
        try {
          $ReportDate = Helper::getTextContent($ReportDate);
        } catch (Exception $e) {
          Logger::warning("ForumParser " . $Fingerprint . ": Unable to parse report time; " . $e->getMessage());
        }
      } else if (!is_string($ReportDate)) {
        Logger::warning("ForumParser " . $Fingerprint . ": Unable to parse report time");
      }

      if ($bLocalFallback===false) {
        preg_match(self::format[$Locale]['day_regex'], $ReportDate, $DayMatches);
      }
      if ($bLocalFallback===false && isset($DayMatches[0])) {
        $Day = $DayMatches[0];
      } else {
        $Day = date(self::format[$Locale]['day']); // Current day
      }
      preg_match(self::format[$Locale]['time_regex'], $ReportDate, $TimeMatches);
      if (isset($TimeMatches[0])) {
        $Time = $TimeMatches[0];
      } else {
        $Time = date(self::format[$Locale]['time']); // Current time
        $cityInfo['print_debug'] = true;
      }
      $cityInfo['mutation_date'] = "$Day $Time";
      try {
        $ParsedDate = Carbon::createFromFormat(self::format[$Locale]['day'] . " " . self::format[$Locale]['time'], "$Day $Time");
        if ($ParsedDate == null) {
          throw new Exception("Parsed date is null");
        } else {
          $oDateMax = new Carbon();
          $oDateMax->addHours(24);
          if ($ParsedDate > $oDateMax) {
            throw new Exception("Parsed date is in the future");
          } else {
            $oDateMin = new Carbon();
            $oDateMin->subDays(150);
            if ($ParsedDate < $oDateMin) {
              Logger::warning("InboxParser ". $Fingerprint . ": Parsed date is too far in the past");
              $cityInfo['print_debug'] = true;
            } else {
              $cityInfo['parsed_date'] = $ParsedDate;
            }
          }
        }
      } catch (Exception $e) {
        throw new ForumParserExceptionError("Exception while parsing date. " . $e->getMessage());
      }

      // Overwrite format
      $cityInfo['mutation_date'] = $ParsedDate->format("d-m-y H:i:s");
    }

    if (isset($aReportData['content'][0]['content'][3]['content']['3']['content'][4]) && $aReportData['content'][0]['content'][3]['content']['3']['content'][4] === ") valt ") {
      throw new ForumParserExceptionWarning("TODO: check report data for enemy attack! this check should not trigger");
    }

    // Find Report header (type=SPAN, class.="BOLD", content >= 3, must contain '(' and ')')
    $aHeaderItems = $aReportData['content'][3]['content'][3]['content'];
    //$HeaderTextContent = Helper::getTextContent($aHeaderItems);

    // Parse header dynamically
    $bContainsEscapedName = false;
    $aHeaderChainData = [];
    $aHeaderChainType = [];
    foreach ($aHeaderItems as $aHeaderItem) {
      if (is_array($aHeaderItem) && key_exists('attributes', $aHeaderItem)) {
        $aAttributes = $aHeaderItem['attributes'];
        if (key_exists('href', $aAttributes) && key_exists('class', $aAttributes)) {
          $LinkDataEncoded = $aAttributes['href'];
          $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
          $Class = $aAttributes['class'];
          if (str_contains($Class, 'gp_town_link')) {
            $aHeaderChainData[] = $aLinkData;
            $aHeaderChainType[] = 'town';
          }
          elseif (str_contains($Class, 'gp_player_link')) {
            $aHeaderChainData[] = $aLinkData;
            $aHeaderChainType[] = 'player';
          }
          else {
            Logger::warning("Unhandled attribute class in forum report header: " . $Class);
          }
        }
      } else if (is_string($aHeaderItem)) {
        if (strpos($aHeaderItem, '(') !== false && strpos($aHeaderItem, ')') !== false) {
          $bContainsEscapedName = true;
        }
      }
    }
    $ReportChainString = join("-", $aHeaderChainType);

    // Ghost check
    $bReceiverIsGhost = false;
    if ($ReportChainString === 'town-town' && ($aHeaderChainData[1]['tp'] === 'ghost_town' || $bContainsEscapedName)) {
      $ReportChainString .= '-ghost';
      $bReceiverIsGhost = true;
    }

    $ReportType = 'UNKNOWN';
    switch ($ReportChainString) {
      case 'town-town-player':
      case 'town-town-ghost':
        // Check spy report
        $bIsSpy = false;
        foreach ($aReportData['content'] as $aReportContent) {
          if (isset($aReportContent['attributes']['class']) && strpos($aReportContent['attributes']['class'], 'espionage_report') !== false) {
            $bIsSpy = true;
          }
        }

        if ($bIsSpy === true) {
          $ReportType = self::report_types['spy'];
        } else {
          $ReportType = self::report_types['friendly'];
        }
        break;
      case 'town-player-town-player':
      case 'town-player-player-town':
      //case sizeof($aHeaderChainType) === 4:
        $ReportType = self::report_types['conquest'];
        break;
      case 'town-player-town':
        $ReportType = self::report_types['enemy'];
        break;
      case 'town':
      case '':
        // Probably a quest or bandit camp.

        // TEMP: validate behavior
        if ($Locale == 'nl') {
          $bStrMatch = false;
          foreach ($aHeaderItems as $aHeaderItem) {
            if (is_string($aHeaderItem) && (strpos($aHeaderItem, '(Bandieten)') !== false || strpos($aHeaderItem, '(Opdracht)') !== false)) {
              $bStrMatch = true;
            }
          }
          if ($bStrMatch == false) {
            throw new ForumParserExceptionError("InboxParser bandit/quest check failed");
          }
        }

        throw new ForumParserExceptionDebug("Empty report chain likely caused by bandit/quest report: " . $ReportChainString);
        break;
      default:
        throw new ForumParserExceptionWarning("Unknown forum parser report chain: " . $ReportChainString);
    }
    $cityInfo['report_type'] = $ReportType;

    switch ($ReportType) {
      case self::report_types['spy']:
        // FRIENDLY TOWN($aHeaderChainData[0]) IS SPYING ON TOWN($aHeaderChainData[1]) OWNED BY PLAYER($aHeaderChainData[2])

        // Town
        $SpiedCity = $aHeaderChainData[1];
        $cityInfo['city_id'] = $SpiedCity['id'];
        $cityInfo['city_name'] = $SpiedCity['name'];

        // Player
        if ($bReceiverIsGhost === false) {
          $AttackedPlayer = $aHeaderChainData[2];
          $cityInfo['player_name'] = $AttackedPlayer['name'];
          $cityInfo['player_id'] = $AttackedPlayer['id'];
        } else {
          $cityInfo['player_name'] = 'Ghost';
          $cityInfo['player_id'] = 0;
        }

        // Check silver
        if (isset($aReportData['content'][5]['content'][5]['content'][4]['content'][0])) {
          $Silver = $aReportData['content'][5]['content'][5]['content'][4]['content'][0];

          $cityInfo['silver_in_cave'] = $Silver;
          $cityInfo['silver_in_cave'] = substr($cityInfo['silver_in_cave'], 5, strlen($cityInfo['silver_in_cave']) - 12);
          if (strpos($cityInfo['silver_in_cave'], '(+') !== FALSE) {
            $bonusPos = strpos($cityInfo['silver_in_cave'], '(+');
            $bonusSilver = substr($cityInfo['silver_in_cave'], $bonusPos);
            $mainSilver = substr($cityInfo['silver_in_cave'], 0, $bonusPos - 9);
            $cityInfo['silver_in_cave'] = $mainSilver . $bonusSilver;
          }
        } else {
          Logger::warning("can not find silver in Forum parser: " . $Fingerprint);
          $cityInfo['silver_in_cave'] = null;
        }

        //loop units
        if (strpos($aReportData["content"][5]["content"][1]["attributes"]["class"], "spy_units") === false) {
          throw new ForumParserExceptionError("Spy report: unable to find spy units");
        }
        $unitsRootArr = $aReportData['content'][5]['content'][1]['content'];
        foreach ($unitsRootArr as $unitsChild) {

          if (is_array($unitsChild) && count($unitsChild) > 1) {
            $Value = $unitsChild['content'][1]['content'][0] ?? null;
            $Class = $unitsChild['attributes']['class'];

            // Parse unit names
            foreach (self::aUnitNames as $Key => $aUnit) {
              if (strpos($Class, $Key)) {
                if ($Value===null) {
                  Logger::warning("Forum parser $Fingerprint: Invalid Value for unit child: " . json_encode($unitsChild));
                }

                if ($aUnit['value'] === null) {
                  // Regular units
                  $cityInfo[$aUnit['type']] = $Value;
                } else {
                  // Hero
                  $cityInfo[$aUnit['type']] = $aUnit['value'];
                }
                if ($aUnit['god'] !== null) {
                  // Set god for mythical unit
                  $cityInfo['god'] = $aUnit['god'];
                }
                break;
              }
            }

          }
        }

        //loop buildings
        if (strpos($aReportData["content"][5]["content"][3]["attributes"]["class"], "spy_buildings") === false) {
          Logger::warning("Forum parser " . $Fingerprint . ": Spy report: unable to find spy buildings");
        }
        $buildsRootArr = $aReportData['content'][5]['content'][3]['content'];
        $bHasWall = false;
        foreach ($buildsRootArr as $buildsChild) {

          if (is_array($buildsChild) && count($buildsChild) > 1) {
            if (strpos($buildsChild['attributes']['class'], "building_main")) {
              $cityInfo['senate'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_lumber")) {
              $cityInfo['wood'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_farm")) {
              $cityInfo['farm'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_stoner")) {
              $cityInfo['stone'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_ironer")) {
              $cityInfo['silver'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_barracks")) {
              $cityInfo['baracks'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_temple")) {
              $cityInfo['temple'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_storage")) {
              $cityInfo['storage'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_market")) {
              $cityInfo['trade'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_docks")) {
              $cityInfo['port'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_academy")) {
              $cityInfo['academy'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_wall")) {
              $cityInfo['wall'] = $buildsChild['content'][1]['content'][0];
              $bHasWall = true;
            }
            if (strpos($buildsChild['attributes']['class'], "building_hide")) {
              $cityInfo['cave'] = $buildsChild['content'][1]['content'][0];
            }
            if (strpos($buildsChild['attributes']['class'], "building_theater")) {
              $cityInfo['special_1'] = "theater";
            }
            if (strpos($buildsChild['attributes']['class'], "building_thermal")) {
              $cityInfo['special_1'] = "badhuis";
            }
            if (strpos($buildsChild['attributes']['class'], "building_library")) {
              $cityInfo['special_1'] = "bibliotheek";
            }
            if (strpos($buildsChild['attributes']['class'], "building_lighthouse")) {
              $cityInfo['special_1'] = "vuurtoren";
            }
            if (strpos($buildsChild['attributes']['class'], "building_tower")) {
              $cityInfo['special_2'] = "toren";
            }
            if (strpos($buildsChild['attributes']['class'], "building_statue")) {
              $cityInfo['special_2'] = "godenbeeld";
            }
            if (strpos($buildsChild['attributes']['class'], "building_oracle")) {
              $cityInfo['special_2'] = "orakel";
            }
            if (strpos($buildsChild['attributes']['class'], "building_trade_office")) {
              $cityInfo['special_2'] = "handelskantoor";
            }
          }

        }
        if ($bHasWall === false) {
          $cityInfo['wall'] = "0";
        }


        // attack: friendly > enemy
        break;
      case self::report_types['friendly']:
        // FRIENDLY TOWN($aHeaderChainData[0]) IS ATTACKING TOWN($aHeaderChainData[1]) FROM PLAYER($aHeaderChainData[2])

        // Town
        $AttackedCity = $aHeaderChainData[1];
        $cityInfo['city_name'] = $AttackedCity['name'];
        $cityInfo['city_id'] = $AttackedCity['id'];

        // Player
        if ($bReceiverIsGhost === false) {
          $AttackedPlayer = $aHeaderChainData[2];
          $cityInfo['player_name'] = $AttackedPlayer['name'];
          $cityInfo['player_id'] = $AttackedPlayer['id'];
        } else {
          $cityInfo['player_name'] = 'Ghost';
          $cityInfo['player_id'] = 0;
        }

        //wall
        $aReportStats = $aReportData['content'][5]['content'][1]['content'][3]['content'][1]['content'][1]['content'][2]['content'][1]['content'][1]['content'][1];
        if (!is_null($aReportStats) && count($aReportStats['content']) == 2) {
          $cityInfo['wall'] = 0;
        } else {
          $cityInfo['wall'] = $aReportStats['content'][2]['content'][1]['content'][2];
          if (is_string($cityInfo['wall']) && strpos($cityInfo['wall'], ": +100%")) {
            $cityInfo['wall'] = 0;
          } else if (is_string($cityInfo['wall']) && preg_match('/[0-9]/', $cityInfo['wall'], $matches, PREG_OFFSET_CAPTURE)) {
            $cityInfo['wall'] = substr($cityInfo['wall'], $matches[0][1]);
            if (strpos($cityInfo['wall'], ')') !== false) {
              $cityInfo['wall'] = substr($cityInfo['wall'], 0, strpos($cityInfo['wall'], ')') + 1);
            } else {
              Logger::warning("ForumParser " . $Fingerprint . ": Unable to find closing brace for wall with value '" . $cityInfo['wall'] . "'");
              $cityInfo['print_debug'] = true;
            }
          } else {
            // no wall level found
            Logger::warning("ForumParser " . $Fingerprint . ": Unable to parse wall with value '" . $cityInfo['wall'] . "'");
            $cityInfo['print_debug'] = true;
          }
        }

        //loop units
        $unitsRootArr = $aReportData['content'][5]['content'][1]['content'][5]['content'];

        foreach ($unitsRootArr as $unitsChildren) {

          if (isset($unitsChildren['attributes']) && isset($unitsChildren['attributes']['class']) &&
            strpos($unitsChildren['attributes']['class'], "report_units_type") !== FALSE) {
            $subArr = $unitsChildren['content'];

            foreach ($subArr as $unitsChild) {

              if (is_array($unitsChild) && count($unitsChild) > 1) {
                $Value = $unitsChild['content'][1]['content'][1]['content'][0];
                $Died = $unitsChild['content'][3]['content'][0];
                $Class = $unitsChild['content'][1]['attributes']['class'];

                // Parse unit names
                foreach (self::aUnitNames as $Key => $aUnit) {
                  if (strpos($Class, $Key)) {
                    if ($aUnit['value'] === null) {
                      // Regular unit (-killed)
                      $cityInfo[$aUnit['type']] = $Value;
                      if (strpos($Died, '-') !== FALSE) {
                        $cityInfo[$aUnit['type']] .= "(" . $Died . ")";
                      }
                    } else {
                      // Hero
                      $cityInfo[$aUnit['type']] = $aUnit['value'];
                    }
                    if ($aUnit['god'] !== null) {
                      // Set god for mythical unit
                      $cityInfo['god'] = $aUnit['god'];
                    }
                    break;
                  }
                }

              }
            }
          }
        }
        break;
      case self::report_types['enemy']:
        // TOWN($aHeaderChainData[0]) FROM PLAYER($aHeaderChainData[1]) IS ATTACKING TOWN($aHeaderChainData[2])

        // Town
        $AttackingCity = $aHeaderChainData[0];
        $cityInfo['city_name'] = $AttackingCity['name'];
        $cityInfo['city_id'] = $AttackingCity['id'];

        // Player
        $AttackingPlayer = $aHeaderChainData[1];
        $cityInfo['player_name'] = $AttackingPlayer['name'];
        $cityInfo['player_id'] = $AttackingPlayer['id'];

        //loop units
        $unitsRootArr = $aReportData['content'][5]['content'][1]['content'][1]['content'];

        $bFoundUnits = false;
        $bTitleTextPresent = false;
        $bSmallTextPresent = false;
        $SmallText = '';
        $bReportUnitsPresent = false;
        foreach ($unitsRootArr as $unitsChildren) {
          if (($Class = $unitsChildren['attributes']['class'] ?? null) && ($Type = $unitsChildren['type'] ?? null)) {
            $Class = strtolower($Class);
            $Type = strtolower($Type);

            if ($Class == "small bold" && $Type == 'div') {
              $bTitleTextPresent = true;
            } else if ($Class == "small" && $Type == 'div') {
              $bSmallTextPresent = true;
              $SmallText = $unitsChildren['content'][0] ?? '';
            } else if (strpos($Class, "report_units_type") !== FALSE) {
              $bReportUnitsPresent = true;
              $subArr = $unitsChildren['content'];

              foreach ($subArr as $unitsChild) {

                if (is_array($unitsChild) && count($unitsChild) > 1 && isset($unitsChild['content'][1]['attributes']['class'])) {
                  $bFoundUnits = true;

                  $Value = $unitsChild['content'][1]['content'][1]['content'][0];
                  $Died = $unitsChild['content'][3]['content'][0];
                  $Class = $unitsChild['content'][1]['attributes']['class'];
                  if (!is_string($Value) && is_array($Value)) {
                    $Value = Helper::getTextContent($Value);
                    Logger::warning("ForumParser " . $Fingerprint . ": unit child value is not string. extracted text: " . $Value);
                  }
                  if (!is_string($Died) && is_array($Died)) {
                    $Died = Helper::getTextContent($Died);
                    Logger::warning("ForumParser " . $Fingerprint . ": unit child died is not string. extracted text: " . $Died);
                  }
                  if (!is_string($Class) && is_array($Class)) {
                    $Class = Helper::getTextContent($Class);
                    Logger::warning("ForumParser " . $Fingerprint . ": unit child class is not string. extracted text: " . $Class);
                  }

                  // Parse unit names
                  foreach (self::aUnitNames as $Key => $aUnit) {
                    if (strpos($Class, $Key)) {
                      if ($aUnit['value'] === null) {
                        // Regular unit (-killed)
                        $cityInfo[$aUnit['type']] = $Value;
                        if (strpos($Died, '-') !== FALSE) {
                          $cityInfo[$aUnit['type']] .= "(" . $Died . ")";
                        }
                      } else {
                        // Hero
                        $cityInfo[$aUnit['type']] = $aUnit['value'];
                      }
                      if ($aUnit['god'] !== null) {
                        // Set god for mythical unit
                        $cityInfo['god'] = $aUnit['god'];
                      }
                      break;
                    }
                  }

                }
              }
            }
          }
        }

        if ($bTitleTextPresent==true && $bSmallTextPresent==true && $bReportUnitsPresent==false) {
          if (
          ($Locale == 'nl' && $SmallText!='Niet zichtbaar.') ||
          ($Locale == 'de' && $SmallText!='Nicht sichtbar.')) {
            throw new ForumParserExceptionError("Hidden troop check invalid for locale. string mismatch");
          }
          throw new ForumParserExceptionDebug("Report units are probably hidden.");
        } else if ($bFoundUnits === false) {
          throw new ForumParserExceptionWarning("Unable to find any units in enemy attack");
        }

        break;
      case self::report_types['conquest']:
        // TOWN($aHeaderChainData[0]) FROM PLAYER($aHeaderChainData[1]) IS ATTACKING TOWN($aHeaderChainData[2]) UNDER CONQUEST BY PLAYER($aHeaderChainData[3])
        // TOWN($aHeaderChainData[0]) FROM PLAYER($aHeaderChainData[1]) IS ATTACKING CONQUEST OF BY PLAYER($aHeaderChainData[2]) ON TOWN($aHeaderChainData[3])

        // Town
        $AttackingCity = $aHeaderChainData[0];
        $cityInfo['city_name'] = $AttackingCity['name'];
        $cityInfo['city_id'] = $AttackingCity['id'];

        // Player
        $AttackingPlayer = $aHeaderChainData[1];
        $cityInfo['player_name'] = $AttackingPlayer['name'];
        $cityInfo['player_id'] = $AttackingPlayer['id'];

        //loop units
        $unitsRootArr = $aReportData['content'][5]['content'][1]['content'][1]['content'];
        foreach ($unitsRootArr as $unitsChildren) {

          if (is_array($unitsChildren)
            && key_exists('attributes', $unitsChildren)
            && key_exists('class', $unitsChildren['attributes'])
            && strpos($unitsChildren['attributes']['class'], "report_units_type") !== FALSE) {
            $subArr = $unitsChildren['content'];

            foreach ($subArr as $unitsChild) {

              if (is_array($unitsChild)
                && count($unitsChild) > 1
                && isset($unitsChild['content'][1]['attributes']['class'])) {
                $Value = $unitsChild['content'][1]['content'][1]['content'][0];
                $Died = $unitsChild['content'][3]['content'][0];
                $Class = $unitsChild['content'][1]['attributes']['class'];

                // Parse unit names
                foreach (self::aUnitNames as $Key => $aUnit) {
                  if (strpos($Class, $Key)) {
                    if ($aUnit['value'] === null) {
                      // Regular unit (-killed)
                      $cityInfo[$aUnit['type']] = $Value;
                      if (strpos($Died, '-') !== FALSE) {
                        $cityInfo[$aUnit['type']] .= "(" . $Died . ")";
                      }
                    } else {
                      // Hero
                      $cityInfo[$aUnit['type']] = $aUnit['value'];
                    }
                    if ($aUnit['god'] !== null) {
                      // Set god for mythical unit
                      $cityInfo['god'] = $aUnit['god'];
                    }
                    break;
                  }
                }

              }
            }
          }
        }

        break;
      default:
        throw new ForumParserExceptionWarning("Report type not handled: " . $ReportType);
    }

    return $cityInfo;
  }

}
