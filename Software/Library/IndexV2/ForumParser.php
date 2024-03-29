<?php

namespace Grepodata\Library\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\IndexV2\IntelShared;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Exception\ForumParserExceptionDebug;
use Grepodata\Library\Exception\ForumParserExceptionError;
use Grepodata\Library\Exception\ForumParserExceptionWarning;
use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Intel;

class ForumParser
{
  const format = array(
    'nl' => array(
      'day' => 'd-m-y', 'day_regex' => '/[0-9]{2}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'se' => array(
      'day' => 'y-m-d', 'day_regex' => '/[0-9]{2}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'de' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'cz' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'no' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'en' => array(
      'day' => 'Y-m-d', 'day_regex' => '/[0-9]{4}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'fr' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'es' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'pt' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'hu' => array(
      'day' => 'y/m/d', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'it' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'us' => array(
      'day' => 'Y-m-d', 'day_regex' => '/[0-9]{4}[-]{1}[0-9]{2}[-]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'dk' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'gr' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'fi' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'ro' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'ru' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'sk' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'tr' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'pl' => array(
      'day' => 'd.m.y', 'day_regex' => '/[0-9]{2}[.]{1}[0-9]{2}[.]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'ar' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'br' => array(
      'day' => 'd/m/y', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
    'zz' => array(
      'day' => 'y/m/d', 'day_regex' => '/[0-9]{2}[\/]{1}[0-9]{2}[\/]{1}[0-9]{2}/',
      'time' => 'H:i:s', 'time_regex' => '/[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2}(?!.*[0-9]{2}[:]{1}[0-9]{2}[:]{1}[0-9]{2})/',
    ),
  );
  const report_types = array(
    'spy'      => 'spy',
    'friendly' => 'friendly_attack',
    'enemy'    => 'enemy_attack',
    'conquest' => 'attack_on_conquest'
  );
  const gods = array('zeus', 'ares', 'aphrodite', 'poseidon', 'hera', 'athene', 'hades', 'artemis');
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
    "unit_siren"              => array('type' => 'siren',       'value' => null,  'god' => 'Aphrodite'),
    "unit_satyr"              => array('type' => 'satyr',       'value' => null,  'god' => 'Aphrodite'),
    "unit_spartoi"            => array('type' => 'spartoi',     'value' => null,  'god' => 'Ares'),
    "unit_ladon"              => array('type' => 'ladon',       'value' => null,  'god' => 'Ares'),

    // Heros
    "unit_achilles"           => array('type' => 'hero',  'value' => 'Achilles',      'god' => null),
    "unit_agamemnon"          => array('type' => 'hero',  'value' => 'Agamemnon',     'god' => null),
    "unit_ajax"               => array('type' => 'hero',  'value' => 'Ajax',          'god' => null),
    "unit_alexandrios"        => array('type' => 'hero',  'value' => 'Alexandrios',   'god' => null),
    "unit_andromeda"          => array('type' => 'hero',  'value' => 'Andromeda',     'god' => null),
    "unit_apheledes"          => array('type' => 'hero',  'value' => 'Apheledes',     'god' => null),
    "unit_argus"              => array('type' => 'hero',  'value' => 'Argus',         'god' => null),
    "unit_aristotle"          => array('type' => 'hero',  'value' => 'Aristotle',     'god' => null),
    "unit_atalanta"           => array('type' => 'hero',  'value' => 'Atalanta',      'god' => null),
    "unit_cheiron"            => array('type' => 'hero',  'value' => 'Cheiron',       'god' => null),
    "unit_christopholus"      => array('type' => 'hero',  'value' => 'Christopholus', 'god' => null),
    "unit_daidalos"           => array('type' => 'hero',  'value' => 'Daidalos',      'god' => null),
    "unit_deimos"             => array('type' => 'hero',  'value' => 'Deimos',        'god' => null),
    "unit_democritus"         => array('type' => 'hero',  'value' => 'Democritus',    'god' => null),
    "unit_deryntes"           => array('type' => 'hero',  'value' => 'Deryntes',      'god' => null),
    "unit_ephialtes"          => array('type' => 'hero',  'value' => 'Ephialtes',     'god' => null),
    "unit_eurybia"            => array('type' => 'hero',  'value' => 'Eurybia',       'god' => null),
    "unit_ferkyon"            => array('type' => 'hero',  'value' => 'Ferkyon',       'god' => null),
    "unit_hector"             => array('type' => 'hero',  'value' => 'Hector',        'god' => null),
    "unit_helen"              => array('type' => 'hero',  'value' => 'Helen',         'god' => null),
    "unit_hercules"           => array('type' => 'hero',  'value' => 'Hercules',      'god' => null),
    "unit_iason"              => array('type' => 'hero',  'value' => 'Iason',         'god' => null),
    "unit_leonidas"           => array('type' => 'hero',  'value' => 'Leonidas',      'god' => null),
    "unit_lysippe"            => array('type' => 'hero',  'value' => 'Lysippe',       'god' => null),
    "unit_medea"              => array('type' => 'hero',  'value' => 'Medea',         'god' => null),
    "unit_melousa"            => array('type' => 'hero',  'value' => 'Melousa',       'god' => null),
    "unit_mihalis"            => array('type' => 'hero',  'value' => 'Mihalis',       'god' => null),
    "unit_odysseus"           => array('type' => 'hero',  'value' => 'Odysseus',      'god' => null),
    "unit_orpheus"            => array('type' => 'hero',  'value' => 'Orpheus',       'god' => null),
    "unit_pariphaistes"       => array('type' => 'hero',  'value' => 'Pariphaistes',  'god' => null),
    "unit_pelops"             => array('type' => 'hero',  'value' => 'Pelops',        'god' => null),
    "unit_perseus"            => array('type' => 'hero',  'value' => 'Perseus',       'god' => null),
    "unit_philoctetes"        => array('type' => 'hero',  'value' => 'Philoctetes',   'god' => null),
    "unit_rekonos"            => array('type' => 'hero',  'value' => 'Rekonos',       'god' => null),
    "unit_telemachos"         => array('type' => 'hero',  'value' => 'Telemachos',    'god' => null),
    "unit_terylea"            => array('type' => 'hero',  'value' => 'Terylea',       'god' => null),
    "unit_themistokles"       => array('type' => 'hero',  'value' => 'Themistokles',  'god' => null),
    "unit_theseus"            => array('type' => 'hero',  'value' => 'Theseus',       'god' => null),
    "unit_urephon"            => array('type' => 'hero',  'value' => 'Urephon',       'god' => null),
    "unit_vedouma"            => array('type' => 'hero',  'value' => 'Vedouma',       'god' => null),
    "unit_xanthos"            => array('type' => 'hero',  'value' => 'Xanthos',       'god' => null),
    "unit_ylestres"           => array('type' => 'hero',  'value' => 'Ylestres',      'god' => null),
    "unit_zuretha"            => array('type' => 'hero',  'value' => 'Zuretha',       'god' => null),
  );

  /**
   * @param $UserId
   * @param $World
   * @param $aReportData
   * @param $ReportHash
   * @param $ReportJson
   * @param $ReportInfo
   * @param $ReportPoster
   * @param $ReportPosterId
   * @param $ReportPosterAllyId
   * @param $ScriptVersion
   * @param string $Locale
   * @param null $DebugReparseIntel Only used for local debugging
   * @param array $aIndexes List of index keys to create conquest overviews for
   * @return Mixed
   * @throws ForumParserExceptionDebug
   * @throws ForumParserExceptionError
   * @throws ForumParserExceptionWarning
   */
  public static function ParseReport(
    $UserId,
    $World,
    $aReportData,
    $ReportHash,
    $ReportJson,
    $ReportInfo,
    $ReportPoster,
    $ReportPosterId,
    $ReportPosterAllyId,
    $ScriptVersion,
    $Locale,
    $DebugReparseIntel = null,
    $aIndexes = array()
  )
  {
    try {
      $aCityInfo = self::ExtractCityInfo($aReportData, $ReportHash, $Locale, $World);

      $AllianceId = 0;
      if ($aCityInfo['player_name'] !== 'Ghost' && $aCityInfo['player_id'] !== 0) {
        $oPlayer = Player::firstById($World, $aCityInfo['player_id']);
        if ($oPlayer === null) {
//          throw new ForumParserExceptionWarning("Unable to find player with name ".$aCityInfo['player_name']." and id ".$aCityInfo['player_id']);
          Logger::warning("ForumParser reporthash $ReportHash: Unable to find player with name ".$aCityInfo['player_name']." and id ".$aCityInfo['player_id']);
        }
        $AllianceId = $oPlayer->alliance_id;
      }

      if (!isset($aCityInfo['city_id']) || is_null($aCityInfo['city_id']) || !isset($aCityInfo['city_name']) || is_null($aCityInfo['city_name'])) {
        throw new ForumParserExceptionWarning("Unable to find report subject city");
      }

      $aBuildingIds = array('senate', 'wood', 'farm', 'stone', 'silver', 'baracks', 'temple', 'storage', 'trade', 'port', 'academy', 'wall', 'cave', 'special_1', 'special_2');
      $aLandUnitIds = array('militia', 'sword', 'sling', 'bow', 'spear', 'caval', 'strijd', 'kata', 'gezant', 'unknown');
      $aMythUnitIds = array('siren', 'satyr', 'spartoi', 'ladon', 'mino', 'manti', 'sea_monster', 'harp', 'medusa', 'centaur', 'pegasus', 'cerberus', 'erinyes', 'cyclope', 'griff', 'boar');
      $aSeaUnitIds  = array('slow_tp', 'bir', 'brander', 'fast_tp', 'trireme', 'kolo', 'unknown_naval');

      $aBuildings = array();
      foreach ($aBuildingIds as $Building) {
        if (isset($aCityInfo[$Building])) $aBuildings[$Building] = $aCityInfo[$Building];
      }
      if (!empty($aCityInfo['parsed_buildings'])) {
        $aBuildings = array_merge($aBuildings, $aCityInfo['parsed_buildings']);
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

      if (is_null($DebugReparseIntel)) {
        $oIntel = new Intel();
        $oIntel->report_json = $ReportJson;
        $oIntel->report_info = json_encode(substr($ReportInfo, 0, 100));
      } else {
        // Reparse existing record in debugger
        $oIntel = $DebugReparseIntel;
      }
      $oIntel->indexed_by_user_id = $UserId;
      $oIntel->hash        = $ReportHash;
      $oIntel->luck        = $aCityInfo['luck']??0;
      $oIntel->world       = $World;
      $oIntel->v1_index    = $World; // This field is only used to allow duplicate entries of V1 intel, new reports should not use this field but it can also not be null otherwise SQL wont enforce the unique index.
      $oIntel->source_type = 'forum';
      $oIntel->report_type = $aCityInfo['report_type'];
      $oIntel->script_version = $ScriptVersion;

      $oIntel->town_id     = $aCityInfo['city_id'];
      $oIntel->town_name   = $aCityInfo['city_name'];
      $oIntel->player_id   = $aCityInfo['player_id'];
      $oIntel->player_name = $aCityInfo['player_name'];
      $oIntel->alliance_id = $AllianceId;
      $oIntel->poster_player_name = $ReportPoster;
      $oIntel->poster_player_id   = $ReportPosterId;
      $oIntel->poster_alliance_id = $ReportPosterAllyId;
      $oIntel->report_date = $aCityInfo['mutation_date'];
      if (isset($aCityInfo['parsed_date'])) {
        $oIntel->parsed_date = $aCityInfo['parsed_date'];
      } else {
        Logger::warning("Unable to parse forum report date. hash: " . $ReportHash . $World);
      }
      $oIntel->hero        = (isset($aCityInfo['hero'])?$aCityInfo['hero']:null);
      $oIntel->god         = (isset($aCityInfo['god'])?$aCityInfo['god']:null);
      $oIntel->silver      = (isset($aCityInfo['silver_in_cave'])?$aCityInfo['silver_in_cave']:null);
      $oIntel->buildings   = json_encode($aBuildings);
      $oIntel->land_units  = json_encode($aLandUnits);
      $oIntel->sea_units   = json_encode($aSeaUnits);
      $oIntel->fireships   = $Fireships;
      $oIntel->mythical_units = json_encode($aMythUnits);

      $oIntel->parsing_failed = false;
      $oIntel->debug_explain = null;

      $bSaved = false;
      try {
        $bSaved = $oIntel->save();
      }
      catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Incorrect datetime value') !== false) {
          // Add an hour and try again to account for EU daylight saving time
          $ParsedDate = $aCityInfo['parsed_date'];
          $ParsedDate->addHour();
          $oIntel->parsed_date = $ParsedDate;
          $bSaved = $oIntel->save();
        } else {
          throw new \Exception("Unable to save City record with error: " . $e->getMessage());
        }
      }

      if ($bSaved) {
        // Check conquest
        try {
          if (isset($aCityInfo['conquest_details']) && !empty($aCityInfo['conquest_details'])) {
            $oIntel->conquest_details = json_encode($aCityInfo['conquest_details']->jsonSerialize());
            $SiegeId = SiegeParser::saveSiegeAttack($aCityInfo['conquest_details'], $oIntel, $aIndexes, $ReportHash);
            if (!empty($SiegeId) && !is_nan($SiegeId) && $SiegeId > 0) {
              $oIntel->conquest_id = $SiegeId;
              $oIntel->save();
            }
          }
        } catch (Exception $e) {
          Logger::warning("ForumParser $ReportHash: unable to update conquest details after creating city object");
        }

        return $oIntel->id;
      } else {
        throw new ForumParserExceptionWarning("Unable to save intel record: " . $oIntel->toJson());
      }
    }
    catch(ForumParserExceptionDebug $e) {throw $e;}
    catch(ForumParserExceptionWarning $e) {throw $e;}
    catch(ForumParserExceptionError $e) {throw $e;}
    catch (\Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // try to find duplicate intel id
        try {
          $oIntel = Intel::where('town_id', '=', $aCityInfo['city_id'])
            ->where('world', '=', $World)
            ->where('parsed_date', '=', $aCityInfo['parsed_date'])
            ->where('report_type', '=', $aCityInfo['report_type'])
            ->where('luck', '=', ($aCityInfo['luck']??0))
            ->firstOrFail();

          try {
            // Duplicate may be caused because the report is also in an enemy index
            // this is normal behavior for conquest reports because their structure is exactly the same for both the attacker and the defender (Hash may be different due to battle point difference)
            // we now need to ensure that the intel is also added to the indexes of the current requestor, but ONLY for the indexes that were not parsed already

            if ($aCityInfo['report_type'] == 'attack_on_conquest' && isset($aCityInfo['conquest_details']) && !empty($aCityInfo['conquest_details'])) {

              Logger::warning("ForumParser $ReportHash; Check duplicate siege parse");

              // We need to skip indexes that were already parsed, otherwise the siege contribution is counted double
              $aAlreadyParsed = IntelShared::allByIntelId($oIntel->id);
              $aIndexesFiltered = array_diff($aIndexes, $aAlreadyParsed);

              if (sizeof($aIndexesFiltered) > 0) {
                // Save siege attack to new indexes
                $oIntel->conquest_details = json_encode($aCityInfo['conquest_details']->jsonSerialize());
                $oIntel->parsed_date = $aCityInfo['parsed_date'];
                $SiegeId = SiegeParser::saveSiegeAttack($aCityInfo['conquest_details'], $oIntel, $aIndexesFiltered, $ReportHash);
                if ($SiegeId != $oIntel->conquest_id) {
                  Logger::warning("ForumParser $ReportHash: siege id mismatch for duplicate city object");
                }
              }
            }
          } catch (Exception $e) {
            Logger::warning("ForumParser $ReportHash: unable to update conquest details after finding duplicate city object");
          }

          return $oIntel->id;
        } catch (\Exception $e) {
          throw new ForumParserExceptionWarning("Unable to find duplicate intel entry");
        }
      } else {
        throw new ForumParserExceptionError("uncaught exception in forum parser: " . $e->getMessage() . '. [' . $e->getTraceAsString() . ']');
      }
    }
  }

  /**
   * @param $aReportData
   * @param $ReportHash
   * @param string $Locale
   * @param $World
   * @return array
   * @throws ForumParserExceptionDebug
   * @throws ForumParserExceptionError
   * @throws ForumParserExceptionWarning
   * @throws \Grepodata\Library\Exception\ParserDefaultWarning
   */
  private static function ExtractCityInfo($aReportData, $ReportHash, $Locale, $World)
  {
    // Initialize with required properties
    $cityInfo = array(
      'luck' => 0
    );

    // Check report
    $ReportClass = $aReportData['attributes']['class'];
    if (strpos($ReportClass, "published_report") === false) {
      throw new ForumParserExceptionError("Invalid report class. report data does not contain published_report");
    }

    // Reformat molehole changes
    //1089611462 MH but has encapsulation
    //1965508403 MH but no encapsulation
    if (
    (isset($aReportData['content'][0]['type']) && $aReportData['content'][0]['type'] === 'I')
    || (
        count(Helper::allByClass($aReportData, 'MHbbcADD'))>0 // Has molehole buttons
        && count($aReportData['content']) < 6 // AND Is missing some children
        && count($aReportData['content'][0]['content']) >= 6 // AND First child has the expected number of children
      )
    ) {
      Logger::warning("ForumParser " . $ReportHash . ": TODO Check molehole parsing");
      $aReportData = $aReportData['content'][0];
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
      $numberCount = preg_match_all( "/[0-9]/", $ReportDate);
      if ($numberCount > 6) {
        // Should contain date
        Logger::error("ForumParser " . $ReportHash . ": TODO add date format for locale " . $Locale . ". date example: " . $ReportDate);
      } else {
        Logger::warning("ForumParser " . $ReportHash . ": TODO add date format for locale " . $Locale . ". date example: " . $ReportDate);
      }
      $Locale = 'nl';
      $bLocalFallback = true;
    }

    if ($ReportDate == null) {
      // Fallback to today
      Logger::warning("ForumParser " . $ReportHash . ": unable to locate report date");
      $cityInfo['mutation_date'] = date(self::format[$Locale]['day'].' '.self::format[$Locale]['time']);
      $cityInfo['parsed_date'] = new Carbon();
    } else {
      if (!is_string($ReportDate) && is_array($ReportDate)) {
        try {
          $ReportDate = Helper::getTextContent($ReportDate);
        } catch (Exception $e) {
          Logger::warning("ForumParser " . $ReportHash . ": Unable to parse report time; " . $e->getMessage());
        }
      } else if (!is_string($ReportDate)) {
        Logger::warning("ForumParser " . $ReportHash . ": Unable to parse report time");
      }

      if ($bLocalFallback===false) {
        preg_match(self::format[$Locale]['day_regex'], $ReportDate, $DayMatches);
      }
      if ($bLocalFallback===false && isset($DayMatches[0])) {
        $Day = $DayMatches[0];
      } else {
        // Get current day in locale timezone
        $oWorld = World::getWorldById($World);
        $Day = $oWorld->getServerTime()->format(self::format[$Locale]['day']);
      }
      preg_match(self::format[$Locale]['time_regex'], $ReportDate, $TimeMatches);
      if (isset($TimeMatches[0])) {
        $Time = $TimeMatches[0];
      } else {
        $Time = date(self::format[$Locale]['time']); // Current time
      }
      $cityInfo['mutation_date'] = "$Day $Time";
      try {
        $ParsedDate = Carbon::createFromFormat(self::format[$Locale]['day'] . " " . self::format[$Locale]['time'], "$Day $Time");
        if ($ParsedDate == null) {
          throw new Exception("Parsed date is null");
        } else {
          $oDateMax = new Carbon();
          $oDateMax->addHours(26);
          if ($ParsedDate > $oDateMax) {
            // subtract 1 day and try again
            Logger::warning("ForumParser ". $ReportHash . ": Parsed date is in the future, subtracting 1 day.");
            $ParsedDate->subDays(1);
            if ($ParsedDate > $oDateMax) {
              throw new Exception("Parsed date is in the future");
            }
          }

          $oDateMin = new Carbon();
          $oDateMin->subDays(150);
          if ($ParsedDate < $oDateMin) {
            Logger::warning("ForumParser ". $ReportHash . ": Parsed date is too far in the past");
          } else {
            $cityInfo['parsed_date'] = $ParsedDate;
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
    $aHeaderItemsMatched = Helper::allByClass($aReportData, 'bold', true);
    if (
      count($aHeaderItemsMatched)<1 ||
      !isset($aHeaderItemsMatched[0]['content']) ||
      strpos(Helper::getTextContent($aHeaderItemsMatched[0]), '(') === false ||
      strpos(Helper::getTextContent($aHeaderItemsMatched[0]), ')') === false
    ) {
      // fallback hardcoded
      Logger::warning("Forum Parser $ReportHash: Unable to parse report header dynamically");
      $aHeaderItems = $aReportData['content'][3]['content'][3]['content'];
    } else {
      $aHeaderItems = $aHeaderItemsMatched[0]['content'];
    }
    //$HeaderTextContent = Helper::getTextContent($aHeaderItems);

    // Parse header dynamically
    $bContainsEscapedName = false;
    $aHeaderChainData = [];
    $aHeaderChainType = [];
    foreach ($aHeaderItems as $aHeaderItem) {
      if (is_array($aHeaderItem) && key_exists('attributes', $aHeaderItem)) {
        $aAttributes = $aHeaderItem['attributes'];
        if (key_exists('href', $aAttributes)) {
          $LinkDataEncoded = $aAttributes['href'];
          if ($LinkDataEncoded == 'javascript:void(0)') {
            // Alliance
            if (key_exists('onclick', $aAttributes) && strpos($aAttributes['onclick'], 'allianceProfile')!==false) {
              $aHeaderChainData[] = $aAttributes['onclick'];
              $aHeaderChainType[] = 'alliance';
            }
          } elseif (key_exists('class', $aAttributes)) {
            $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
            $Class = $aAttributes['class'];
            $aHeaderChainData[] = $aLinkData;
            if (key_exists('tp', $aLinkData) && $aLinkData['tp'] == 'temple') {
              $aHeaderChainType[] = 'temple';
            }
            elseif (str_contains($Class, 'gp_town_link')) {
              $aHeaderChainType[] = 'town';
            }
            elseif (str_contains($Class, 'gp_player_link')) {
              $aHeaderChainType[] = 'player';
            }
            else {
              Logger::warning("Forum Parser $ReportHash: Unhandled attribute class in forum report header: " . $Class);
            }
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
    $bIsAttackOnTemple = false;
    $bParseLuck = false;
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
          $bParseLuck = true;
        }
        break;
      case 'town-player-town-player':
      case 'town-player-player-town':
      case 'town-player-temple-player':
        //case sizeof($aHeaderChainType) === 4:
        $ReportType = self::report_types['conquest'];
        $bParseLuck = true;
        break;
      case 'town-player-town': // enemy attack on a friendly city
      case 'town-player-player': // enemy attack on friendly conquest of a ghost city
        $ReportType = self::report_types['enemy'];
        $bParseLuck = true;
        break;
      case 'town-town-alliance':
        // Attack on a temple
        $bIsAttackOnTemple = true;
        $ReportType = self::report_types['enemy'];
        $bParseLuck = true;
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
      case 'town-temple':
        throw new ForumParserExceptionDebug("Attack on temple is ignored: " . $ReportChainString);
        break;
      default:
        throw new ForumParserExceptionWarning("Unknown forum parser report chain: " . $ReportChainString);
    }
    $cityInfo['report_type'] = $ReportType;

    if ($bParseLuck) {
      // Parse luck
      // Luck is used to enforce uniqueness between inbox and forum intel (same report from different source should be blocked)
      try {
        $aReportDetailsTable = Helper::allByClass($aReportData, 'report_details');
        if (count($aReportDetailsTable)!==1) {
          throw new Exception("found an invalid number of report_details elements");
        }
        $aReportDetailsTable = $aReportDetailsTable[0];

        $bHasLuck = 1 === count(Helper::allByClass($aReportDetailsTable, 'report_icon luck'));
        if ($bHasLuck) {
          $DetailsText = Helper::getTextContent($aReportDetailsTable);
          $FoundMatches = preg_match_all('/-?\d{1,3} ?%/m', $DetailsText, $aPercentages, PREG_SET_ORDER, 0);

          $bHasMorale = 1 === count(Helper::allByClass($aReportDetailsTable, 'report_icon morale'));
          $bHasNight  = 1 === count(Helper::allByClass($aReportDetailsTable, 'report_icon night'));

          $ExpectedMatches = 1 + ($bHasMorale?1:0) + ($bHasNight?1:0); // luck ?+ morale ?+ night

          if ($ExpectedMatches !== $FoundMatches) {
            throw new Exception("Expected $ExpectedMatches matches, found $FoundMatches matches");
          }

          $LuckMatch = $bHasMorale?$aPercentages[1][0]:$aPercentages[0][0];
          $LuckMatch = str_replace(' ', '', $LuckMatch);
          $LuckMatch = str_replace('%', '', $LuckMatch);

          if (!is_numeric($LuckMatch)) {
            throw new Exception("found non numeric luck value");
          }
          if ($LuckMatch<-30 || $LuckMatch>30) {
            throw new Exception("found an out of bounds luck value");
          }

          $cityInfo['luck'] = $LuckMatch;
        }
        //else {
        // no luck element found, probably spy or support report
        //}
      } catch (Exception $e) {
        Logger::warning("Forum parser $ReportHash: Error parsing luck; ".$e->getMessage()." [".$e->getTraceAsString()."]");
      }
    }

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
        $aSilverItems = Helper::allByClass($aReportData, 'spy_payed');
        if (count($aSilverItems) > 0) {
          $aTextItems = Helper::allByClass($aSilverItems[0], 'small bold');
          if (count($aTextItems) >= 2) {
            $Silver = Helper::getTextContent($aTextItems[1], 0, true);
            $Silver = preg_replace('/\s+/', '', $Silver);

            $cityInfo['silver_in_cave'] = $Silver;
            preg_match_all('/[0-9]{1,}/', $Silver, $numbers);
            if (is_array($numbers[0]) && count($numbers[0]) == 2) {
              $cityInfo['silver_in_cave'] = $numbers[0][0] + $numbers[0][1];
            }
          } else {
            Logger::warning("invalid silver text items in Forum parser: " . $ReportHash);
            $cityInfo['silver_in_cave'] = null;
          }
        } else {
          Logger::warning("can not find silver in Forum parser: " . $ReportHash);
          $cityInfo['silver_in_cave'] = null;
        }

        // Check god
        $aGodItems = Helper::allByClass($aReportData, 'god_display');
        if (count($aGodItems) >= 1) {
          $God = strtolower(Helper::getTextContent($aGodItems[0], 0, true));
          $God = preg_replace('/\s+/', '', $God);
          if (in_array($God, self::gods)) {
            $cityInfo['god'] = $God;
          }
        }

        //loop units (spy unit structure remains unchanged as of Feb 2022)
        if (strpos($aReportData["content"][5]["content"][1]["attributes"]["class"], "spy_units") === false) {
          // Try to find spy units dynamically
          $unitsRootArr = Helper::allByClass($aReportData, "spy_units", false);
          if (empty($unitsRootArr)) {
            throw new ForumParserExceptionError("Spy report: unable to find spy units");
          }
          $unitsRootArr = $unitsRootArr[0]['content'];
        } else {
          $unitsRootArr = $aReportData['content'][5]['content'][1]['content'];
        }
        foreach ($unitsRootArr as $unitsChild) {

          if (is_array($unitsChild) && count($unitsChild) > 1) {
            $Value = $unitsChild['content'][1]['content'][0] ?? null;
            $Class = $unitsChild['attributes']['class'];

            // Parse unit names
            foreach (self::aUnitNames as $Key => $aUnit) {
              if (strpos($Class, $Key)) {
                if ($Value===null) {
                  Logger::warning("Forum parser $ReportHash: Invalid Value for unit child: " . json_encode($unitsChild));
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
          // Try to find spy buildings dynamically
          $buildsRootArr = Helper::allByClass($aReportData, "spy_buildings", false);
          if (empty($buildsRootArr)) {
            Logger::warning("Forum parser " . $ReportHash . ": Spy report: unable to find spy buildings");
          }
          $buildsRootArr = $buildsRootArr[0]['content'];
        } else {
          $buildsRootArr = $aReportData['content'][5]['content'][3]['content'];
        }
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

        // Parse buildings (stonehail + wall)
        // TODO: get translations from client: Object.values(GameData.buildings).map(function(e) {tmp[e.controller] = e.name});
        $aBuildingNames = null;
        if ($Locale == 'nl') {
          $aBuildingNames = array(
            'academy' => 'Academie', 'barracks' => 'Kazerne', 'docks' => 'Haven', 'farm' => 'Boerderij', 'hide' => 'Grot', 'ironer' => 'Zilvermijn', 'library' => 'Bibliotheek', 'lighthouse' => 'Vuurtoren', 'lumber' => 'Houthakkerskamp', 'main' => 'Senaat', 'market' => 'Marktplaats', 'oracle' => 'Orakel', 'place' => 'Agora', 'statue' => 'Godenbeeld', 'stoner' => 'Steengroeve', 'storage' => 'Pakhuis', 'temple' => 'Tempel', 'theater' => 'Theater', 'thermal' => 'Badhuis', 'tower' => 'Toren', 'trade_office' => 'Handelskantoor', 'wall' => 'Stadsmuur',
          );
        } else if ($Locale == 'fr') {
          $aBuildingNames = array(
            'main' => 'Sénat', 'hide' => 'Grotte', 'place' => 'Agora', 'lumber' => 'Scierie', 'stoner' => 'Carrière', 'ironer' => "Mine d'argent", 'market' => 'Marché', 'docks' => 'Port', 'barracks' => 'Caserne', 'wall' => 'Remparts', 'storage' => 'Entrepôt ', 'farm' => 'Ferme', 'academy' => 'Académie', 'temple' => 'Temple', 'theater' => 'Théâtre', 'thermal' => 'Thermes', 'library' => 'Bibliothèque', 'lighthouse' => 'Phare', 'tower' => 'Tour', 'statue' => 'Statue divine', 'oracle' => 'Oracle', 'trade_office' => 'Comptoir commercial',
          );
        } else if ($Locale == 'en') {
          $aBuildingNames = array(
            "main" => "Senate", "hide" => "Cave", "place" => "Agora", "lumber" => "Timber camp", "stoner" => "Quarry", "ironer" => "Silver mine", "market" => "Marketplace", "docks" => "Harbor", "barracks" => "Barracks", "wall" => "City wall", "storage" => "Warehouse", "farm" => "Farm", "academy" => "Academy", "temple" => "Temple", "theater" => "Theater", "thermal" => "Thermal baths", "library" => "Library", "lighthouse" => "Lighthouse", "tower" => "Tower", "statue" => "Divine statue", "oracle" => "Oracle", "trade_office" => "Merchant's shop",
          );
        } else if ($Locale == 'de') {
          $aBuildingNames = array(
            "main" => "Senat", "hide" => "Höhle", "place" => "Agora", "lumber" => "Holzfäller", "stoner" => "Steinbruch", "ironer" => "Silbermine", "market" => "Marktplatz", "docks" => "Hafen", "barracks" => "Kaserne", "wall" => "Stadtmauer", "storage" => "Lager", "farm" => "Bauernhof", "academy" => "Akademie", "temple" => "Tempel", "theater" => "Theater", "thermal" => "Therme", "library" => "Bibliothek", "lighthouse" => "Leuchtturm", "tower" => "Turm", "statue" => "Götterstatue", "oracle" => "Orakel", "trade_office" => "Handelskontor",
          );
        }

        $aReportStats = $aReportData['content'][5]['content'][1]['content'][3]['content'][1]['content'][1]['content'][2]['content'][1]['content'][1]['content'][1]['content'] ?? null;
        if (empty($aReportStats)) {
          throw new ForumParserExceptionWarning("Unable to find forum report stats");
        }
        self::ParseForumReportStats($cityInfo, $aReportStats, $ReportHash, $aBuildingNames);

        //loop units
        $aEnemyUnits = Helper::allByClass($aReportData, 'report_side_defender_unit');
        if (
          is_array($aEnemyUnits)
          && count($aEnemyUnits) > 0
          && (
            strpos(json_encode($aEnemyUnits), 'data-unit_count') !== false // defender has the data-unit_count attribute for at least 1 unit
            || substr_count(json_encode($aEnemyUnits), '"id":"unknown"') == 2 // defender naval and land is unknown
            || substr_count(json_encode($aEnemyUnits), 'unit_icon') == 0 // defender town is empty
          )
          ) {
          // new parsing method (Post Feb 2022)
          self::parseReportUnitsNewFormat($cityInfo, $aEnemyUnits, $ReportHash);
        } else {
          // old parsing method (Pre Feb 2022)
          $unitsRootArr = $aReportData['content'][5]['content'][1]['content'][5]['content'] ?? null;
          if (empty($unitsRootArr)) {
            throw new ForumParserExceptionWarning("Unable to find forum report units array");
          }

          $bFoundUnits = false;
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
                      $bFoundUnits = true;
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

          if ($bFoundUnits) {
            Logger::warning("ForumParser " . $ReportHash . ": using old parsing method. ");
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
        if ($bIsAttackOnTemple===false) {
          $AttackingPlayer = $aHeaderChainData[1];
          $cityInfo['player_name'] = $AttackingPlayer['name'];
          $cityInfo['player_id'] = $AttackingPlayer['id'];
        } else {
          $cityInfo['player_name'] = 'Unknown';
          $cityInfo['player_id'] = 0;
          // Lookup temple attack info
          try {
            // player id
            $oTown = Town::firstById($World, $AttackingCity['id']);
            $cityInfo['player_id'] = $oTown->player_id ?? 0;

            // town
            $oPlayer = Player::firstById($World, $oTown->player_id);
            $cityInfo['player_name'] = $oPlayer->name ?? 'Unknown';
          } catch (Exception $e) {
            Logger::warning("ForumParser " . $ReportHash . ": Error looking up town for temple attack. " . $e->getMessage());
          }
        }

        //loop units
        $aEnemyUnits = Helper::allByClass($aReportData, 'report_side_attacker_unit');
        if (is_array($aEnemyUnits) && count($aEnemyUnits) > 0 && strpos(json_encode($aEnemyUnits), 'data-unit_count') !== false) {
          // new parsing method (Post Feb 2022)
          self::parseReportUnitsNewFormat($cityInfo, $aEnemyUnits, $ReportHash);
        } else {
          // old parsing method (Pre Feb 2022)

          if (count($aReportData['content']) >= 6) {
            $unitsRootArr = $aReportData['content'][5]['content'][1]['content'][1]['content'];
          } else {
            throw new ForumParserExceptionWarning("Unable to find unit parent element in enemy attack");
          }

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
                      Logger::warning("ForumParser " . $ReportHash . ": unit child value is not string. extracted text: " . $Value);
                    }
                    if (!is_string($Died) && is_array($Died)) {
                      $Died = Helper::getTextContent($Died);
                      Logger::warning("ForumParser " . $ReportHash . ": unit child died is not string. extracted text: " . $Died);
                    }
                    if (!is_string($Class) && is_array($Class)) {
                      $Class = Helper::getTextContent($Class);
                      Logger::warning("ForumParser " . $ReportHash . ": unit child class is not string. extracted text: " . $Class);
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

          if ($bTitleTextPresent == true && $bSmallTextPresent == true && $bReportUnitsPresent == false) {
            if (
              ($Locale == 'nl' && $SmallText != 'Niet zichtbaar.') ||
              ($Locale == 'de' && $SmallText != 'Nicht sichtbar.')) {
              throw new ForumParserExceptionError("Hidden troop check invalid for locale. string mismatch");
            }
            throw new ForumParserExceptionDebug("Report units are probably hidden.");
          }

          if ($bFoundUnits) {
            Logger::warning("ForumParser " . $ReportHash . ": using old parsing method. ");
          }
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

        // Conquest details
        $oConquestDetails = new ConquestDetails();
        try {
          if ($ReportChainString == 'town-player-town-player' || $ReportChainString == 'town-player-temple-player') {
            // Dutch version
            $BesiegedTown = $aHeaderChainData[2];
            $SiegeLeadBy = $aHeaderChainData[3];
          } else if ($ReportChainString == 'town-player-player-town') {
            // German version
            $BesiegedTown = $aHeaderChainData[3];
            $SiegeLeadBy = $aHeaderChainData[2];
          } else {
            throw new Exception("Unhandled conquest chain: " . $ReportChainString);
          }
          $oConquestDetails->siegeTownId = $BesiegedTown['id'];
          $oConquestDetails->siegeTownName = $BesiegedTown['name'];
          $oConquestDetails->siegePlayerId = $SiegeLeadBy['id'];
          $oConquestDetails->siegePlayerName = $SiegeLeadBy['name'];
          $oConquestDetails->wall = 0;
          try {
            // find wall level
            $aReportStats = $aReportData['content'][5]['content'][1]['content'][3]['content'][1]['content'][1]['content'][2]['content'][1]['content'][1]['content'][1]['content'] ?? null;
            if (empty($aReportStats)) {
              throw new Exception("Unable to find forum report stats");
            }
            self::ParseForumReportStats($aParsedStats, $aReportStats, $ReportHash);
            if (isset($aParsedStats['wall'])) {
              $oConquestDetails->wall = $aParsedStats['wall'];
            }
          } catch (Exception $e) {
            Logger::warning("ForumParser $ReportHash: error parsing ongoing conquest wall; " . $e->getMessage());
          }
        } catch (Exception $e) {
          Logger::warning("ForumParser $ReportHash: error parsing ongoing conquest details; " . $e->getMessage());
        }

        //loop units
        $aAttackUnits = Helper::allByClass($aReportData, 'report_side_attacker_unit');
        $aDefenderUnits = Helper::allByClass($aReportData, 'report_side_defender_unit');

        if (empty($aAttackUnits) || empty($aDefenderUnits)) {
          throw new ForumParserExceptionWarning("Unable to locate report units");
        }

        // parse incoming
        self::parseReportUnitsNewFormat($cityInfo, $aAttackUnits, $ReportHash);

        // parse siege units
        try {
          self::parseReportUnitsNewFormat($aSiegeUnits, $aDefenderUnits, $ReportHash, true);
          if (empty($aSiegeUnits)) throw new ForumParserExceptionWarning("Unable to parse siege units");
          $oConquestDetails->siegeUnits = $aSiegeUnits;

          $cityInfo['conquest_details'] = $oConquestDetails;
        } catch (Exception $e) {
          Logger::error("ForumParser $ReportHash: error parsing ongoing conquest units; " . $e->getMessage());
        }

        if (isset($aSiegeUnits) && !empty($aSiegeUnits) && (key_exists('unknown_naval', $aSiegeUnits) || key_exists('unknown', $aSiegeUnits))) {
          // Friendly attack on conquest is ignored
          // TODO: consider it as a friendly attack on an enemy town and save wall level for besieged town
          throw new ForumParserExceptionDebug("Ignoring friendly attack on a conquest");
        }

        break;
      default:
        throw new ForumParserExceptionWarning("Report type not handled: " . $ReportType);
    }

    return $cityInfo;
  }

  private static function ParseForumReportStats(&$cityInfo, $aReportStats, $ReportHash, $aBuildingNames = array())
  {
    $cityInfo['wall'] = 0;
    $cityInfo['parsed_buildings'] = array();
    try {
      foreach ($aReportStats as $aStats) {
        $Class = $aStats["content"][1]["content"][1]["attributes"]["class"] ?? null;
        if (empty($Class)) continue;

        $Value = $aStats["content"][1]["content"][2] ?? null;
        if (empty($Value) || !is_string($Value)) continue;

        preg_match('/[0-9]{1,2} \(-[0-9]{1,2}\)/', $Value, $aMatches);
        if (empty($aMatches)) continue;

        if (strpos($Class, 'catapult') !== false) {
          $cityInfo['wall'] = $aMatches[0];
        } else if (strpos($Class, 'stone_hail') !== false && !empty($aBuildingNames)) {
          foreach ($aBuildingNames as $Key => $Name) {
            if (strpos($Value, $Name) !== false) {
              $cityInfo['parsed_buildings'][$Key] = $aMatches[0];
            }
          }
        }
      }

    } catch (Exception $e) {
      Logger::warning("ForumParser " . $ReportHash . ": error parsing report stats; " . $e->getMessage());
    }
  }

  /**
   * Parse units from one attack side
   * @param $cityInfo
   * @param $aReportUnits
   * @param false $bUseRealUnitName If set to true, the original css class of the unit is used instead of the legacy GrepoData ForumParser unit name (e.g. 'bireme' instead of 'bir')
   * @throws \Grepodata\Library\Exception\ParserDefaultWarning
   */
  private static function parseReportUnitsNewFormat(&$cityInfo, $aReportUnits, $ReportHash, $bUseRealUnitName = false) {
    foreach ($aReportUnits as $unitsChild) {

      if (is_array($unitsChild) && count($unitsChild) > 1) {
        $oReportUnit = Helper::allByClass($unitsChild, 'report_unit')[0];
        $Value = $oReportUnit['attributes']['data-unit_count'];
        if (!key_exists('data-unit_count', $oReportUnit['attributes']) && $oReportUnit['attributes']['id']!='unknown' && $oReportUnit['attributes']['id']!='unknown_naval') {
          Logger::warning("ForumParser " . $ReportHash . ": unit_count not found; ");
        }
        $Class = $oReportUnit['attributes']['class'];

        $oDied = Helper::allByClass($unitsChild, 'report_losts')[0];
        $Died = $oDied['content'][0];

        // Parse unit names
        foreach (self::aUnitNames as $Key => $aUnit) {

          if ($bUseRealUnitName == false && strpos($Class, $Key) !== false) {
            if ($aUnit['value'] === null) {
              // Regular unit (-killed)

              if ($Key === 'unit_spartoi') {
                $Value = $oReportUnit["content"][1]["content"][0];
                if (!is_numeric($Value)) {
                  Logger::warning("ForumParser " . $ReportHash . ": unable to parse spartoi value; ");
                }
              }

              $cityInfo[$aUnit['type']] = $Value;
              if (strpos($Died, '-') !== FALSE) {
                if ($Key === 'unknown' || $Key === 'unknown_naval') {
                  $Died = '-?';
                }
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
          } elseif ($bUseRealUnitName == true) {
            // dont translate unit name!
            $rawUnitName = str_replace('unit_','',$Key);
            if (strpos($Class, $Key) !== false) {
              $cityInfo[$rawUnitName] = $Value;
              if (strpos($Died, '-') !== FALSE) {
                $cityInfo[$rawUnitName] .= "(" . $Died . ")";
              }
            }
          }

        }

      }
    }
  }

}
