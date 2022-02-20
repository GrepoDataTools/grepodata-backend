<?php

namespace Grepodata\Library\IndexV2;


use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Exception\InboxParserExceptionDebug;
use Grepodata\Library\Exception\InboxParserExceptionError;
use Grepodata\Library\Exception\InboxParserExceptionWarning;
use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Intel;

class InboxParser
{
  const locales = array(
    'nl' => array('date_format' => 'd-m-Y H:i:s'),
    'en' => array('date_format' => 'Y-m-d H:i:s'),
    'us' => array('date_format' => 'Y-m-d H:i:s'),
    'se' => array('date_format' => 'Y-m-d H:i:s'), // 2020-05-09 23:54:53
    'de' => array('date_format' => 'd.m.Y H:i:s'),
    'fi' => array('date_format' => 'd.m.Y H:i:s'),
    'ro' => array('date_format' => 'd.m.Y H:i:s'),
    'ru' => array('date_format' => 'd.m.Y H:i:s'),
    'sk' => array('date_format' => 'd.m.Y H:i:s'),
    'tr' => array('date_format' => 'd.m.Y H:i:s'), // 09.02.2021 11:22:18
    'pl' => array('date_format' => 'd.m.Y H:i:s'),
    'cz' => array('date_format' => 'd.m.Y H:i:s'),
    'no' => array('date_format' => 'd.m.Y H:i:s'),
    'fr' => array('date_format' => 'd/m/Y H:i:s'),
    'es' => array('date_format' => 'd/m/Y H:i:s'),
    'gr' => array('date_format' => 'd/m/Y H:i:s'),
    'pt' => array('date_format' => 'd/m/Y H:i:s'),
    'dk' => array('date_format' => 'd/m/Y H:i:s'),
    'ar' => array('date_format' => 'd/m/Y H:i:s'),
    'br' => array('date_format' => 'd/m/Y H:i:s'),
    'it' => array('date_format' => 'd/m/Y H:i:s'),
    'hu' => array('date_format' => 'Y/m/d H:i:s'), // 2020/01/15 17:55:24
    'zz' => array('date_format' => 'Y/m/d H:i:s'),
  );
  const land_units = array('sword', 'slinger', 'archer', 'hoplite', 'rider', 'chariot', 'catapult', 'godsent', 'militia');
  const myth_units = array(
    'poseidon'  => array('sea_monster', 'zyklop'),
    'hera'      => array('harpy', 'medusa'),
    'zeus'      => array('minotaur','manticore'),
    'athena'    => array('centaur', 'pegasus'),
    'hades'     => array('cerberus', 'fury'),
    'artemis'   => array('griffin', 'calydonian_boar'),
    'aphrodite' => array('satyr', 'siren'),
    'ares'      => array('spartoi', 'ladon')
  );
  const myth_sea_units = array('sea_monster', 'siren');
  const sea_units  = array('big_transporter', 'bireme', 'attack_ship', 'demolition_ship', 'small_transporter', 'trireme', 'colonize_ship');
  const heros      = array('cheiron', 'ferkyon', 'orpheus', 'terylea', 'andromeda', 'odysseus', 'democritus', 'apheledes', 'christopholus', 'aristotle', 'rekonos', 'ylestres', 'pariphaistes', 'daidalos', 'eurybia', 'leonidas', 'urephon', 'zuretha', 'hercules', 'helen', 'atalanta', 'iason', 'hector', 'agamemnon', 'deimos', 'pelops', 'themistokles', 'telemachos', 'medea', 'ajax', 'alexandrios');
  const fireships  = 'attack_ship';
  const kolo       = 'colonize_ship';

  /**
   * @param $UserId
   * @param $World
   * @param $aReportData
   * @param $ReportPoster
   * @param $PosterId
   * @param $PosterAllyId
   * @param $bAttackerHasCombatExperience
   * @param $ReportHash
   * @param $ReportJson
   * @param $ReportInfo
   * @param $ScriptVersion
   * @param string $Locale
   * @param null $DebugReparseIntel
   * @param array $aIndexes
   * @return int $Id inserted intel id
   * @throws InboxParserExceptionError
   * @throws InboxParserExceptionWarning
   */
  public static function ParseReport(
    $UserId,
    $World,
    $aReportData,
    $ReportPoster,
    $PosterId,
    $PosterAllyId,
    $bAttackerHasCombatExperience,
    $ReportHash,
    $ReportJson,
    $ReportInfo,
    $ScriptVersion,
    $Locale='nl',
    $DebugReparseIntel = null,
    $aIndexes = array()
  )
  {
    try {
      $ReportText = json_encode($aReportData);

      // Find header
//      $ReportHeaderText = $aReportData["content"][1]["content"][17]["content"];
      $ReportHeaderContent = $aReportData["content"][1]["content"][19]["content"][1];
      if ($ReportHeaderContent['type'] == "SCRIPT") {
        $aSpyScript = $ReportHeaderContent["content"][0];
        $ReportHeaderContent = $aReportData["content"][1]["content"][19]["content"][3];
      }

      // Header paths
      $ReportSender = $ReportHeaderContent["content"][1]["content"][3];
      $ReportAction = $ReportHeaderContent["content"][3];
      $ReportReceiver = $ReportHeaderContent["content"][5]["content"][3];

      $ReportImage = $ReportAction["content"][1]["content"][1]['content'][0]["attributes"]["src"];
      if ($ReportImage === null) {
        throw new InboxParserExceptionWarning("InboxParser ". $ReportHash . ": Unable to locate ReportImage");
      }

      $bSpy = false;
      $bSupport = false;
      $bWisdom = false;
      if (strpos($ReportImage, "espionage") !== false) {
        $bSpy = true;
      } else if (strpos($ReportImage, "/images/game/towninfo/support.png") !== false) {
        $bSupport = true;
      }  else if (strpos($ReportText, "power_icon86x86 wisdom") !== false) {
        $bWisdom = true;
      } else if (
        strpos($ReportImage, "/images/game/towninfo/attack.png") === false
        && strpos($ReportImage, "/images/game/towninfo/breach.png") === false
        && strpos($ReportImage, "/images/game/towninfo/attackSupport.png") === false
        && strpos($ReportImage, "/images/game/towninfo/take_over.png") === false) {
        // Ignore non-attack report
        throw new InboxParserExceptionDebug("Ignored non attack report");
      }

      // Report date format
      $ReportDateString = $aReportData["content"][1]["content"][21]["content"][1]["content"][0] ?? null; //03-11-2018 22:49:20
      if ($ReportDateString === null) {
        $ReportDateString = $aReportData["content"][1]["content"][21]["content"][3]["content"][0] ?? null; //03-11-2018 22:49:20
        if ($ReportDateString === null) {
          throw new InboxParserExceptionWarning('report date not found');
        }
      }
      $bFallback = false;
      if (isset(self::locales[$Locale])) {
        $Format = self::locales[$Locale]['date_format'];
      } else {
        Logger::error("TODO: Add InboxParser locale for '" . $Locale . "'. Sample date: $ReportDateString");

        // Fallback to old catch method
        $Format = "d-m-Y H:i:s";
        if (strpos($ReportDateString,'-') >= 4 && substr_count($ReportDateString,'-')==2&&substr_count($ReportDateString,':')==2) {
          $Format = "Y-m-d H:i:s"; // EN
        } else if (substr_count($ReportDateString,'-')==2&&substr_count($ReportDateString,':')==2) {
          $Format = "d-m-Y H:i:s"; // NL
        } else if (substr_count($ReportDateString,'.')==2&&substr_count($ReportDateString,':')==2) {
          $Format = "d.m.Y H:i:s"; // DE
        } else if (substr_count($ReportDateString,'/')==2&&substr_count($ReportDateString,':')==2) {
          $substring = substr($ReportDateString, strpos($ReportDateString, '/', strpos($ReportDateString, '/')+1));
          if (strpos($substring, ' ') == 3) {
            // Third DATE part has a length of 2. this means third part is 'd' => Y/m/d
            $Format = "Y/m/d H:i:s"; // HU
          } else {
            // strpos($substring, ' ') == 5
            // Third DATE part has a length of 4. this means third part is 'Y' => d/m/Y
            $Format = "d/m/Y H:i:s"; // FR
          }
        } else {
          Logger::warning("date does not match any known format: " . $ReportDateString);
          $bFallback = true;
        }
      }

      // Apply format
      if ($bFallback === true) {
        // use current time as fallback
        $oDate = new Carbon();
      } else {
        if (is_array($ReportDateString)) {
          $ReportDateString = Helper::getTextContent($ReportDateString);
        }
        try {
          $oDate = Carbon::createFromFormat($Format, trim($ReportDateString));
        } catch (\Exception $e) {
          throw new InboxParserExceptionWarning("Error creating inbox date: " . $e->getMessage());
        }
        if ($oDate == null) {
          throw new InboxParserExceptionError("Parsed date is null");
        }
      }

      $oDateMax = new Carbon();
      $oDateMax->addHours(26);
      if ($oDate > $oDateMax) {
        // subtract 1 day and try again
        Logger::warning("InbooxParser ". $ReportHash . ": Parsed date is in the future, subtracting 1 day.");
        $oDate->subDays(1);
        if ($oDate > $oDateMax) {
          throw new InboxParserExceptionError("Parsed date is in the future");
        }
      }
      $oDateMin = new Carbon();
      $oDateMin->subDays(150);
      if ($oDate < $oDateMin) {
        Logger::warning("InboxParser ". $ReportHash . ": Parsed date is too far in the past");
      }
      $ReportDate = $oDate->format("d-m-y H:i:s");
      $ParsedDate = $oDate;

      // === Sender
      $bPlayerIsSender = false;
      if ($bWisdom == false) {
        // Town
        $SenderTown = $ReportSender["content"][1]["content"][1];
        if ($SenderTown !== null && isset($SenderTown['attributes']['href'])) {
          $LinkDataEncoded = $SenderTown['attributes']['href'];
          $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
          $SenderTownId = $aLinkData['id'];
          $SenderTownName = $aLinkData['name'];
        } else {
          throw new InboxParserExceptionWarning("Sender town not found in report");
        }

        // Player
        $SenderPlayer = $ReportSender["content"][3]["content"][1] ?? null;
        if ($SenderPlayer !== null && isset($SenderPlayer['attributes']['href'])) {
          $LinkDataEncoded = $SenderPlayer['attributes']['href'];
          $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
          $SenderPlayerId = $aLinkData['id'];
          $SenderPlayerName = $aLinkData['name'];
        } else {
          throw new InboxParserExceptionWarning("Sender player not found in report");
        }

        // Alliance
        $SenderAllianceId = 0;
        $SenderAlliance = $ReportSender["content"][5]["content"][1] ?? null;
        if ($SenderAlliance !== null && isset($SenderAlliance['attributes']['onclick'])) {
          $Onclick = $SenderAlliance['attributes']['onclick'];
          if (strpos($Onclick, 'allianceProfile') !== false) {
            $OnclickSub = substr($Onclick, strrpos($Onclick, "',") + 2);
            $SenderAllianceId = substr($OnclickSub, 0, strlen($OnclickSub) - 1);
          }
        }
        if (($SenderAllianceId === 0 || !is_numeric($SenderAllianceId)) && !is_null($SenderPlayerId) && is_numeric($SenderPlayerId)) {
          $oPlayer = Player::firstById($World, $SenderPlayerId);
          if ($oPlayer !== null) {
            $SenderAllianceId = $oPlayer->alliance_id;
          } else {
            $SenderAllianceId = 0;
          }
        }

        if ($PosterId === $SenderPlayerId || $ReportPoster === $SenderPlayerName) {
          $bPlayerIsSender = true;
        }
      }

      // === Receiver
      $bPlayerIsReceiver = false;
      $bReceiverIsGhost = false;
      // Town
      $ReceiverTown = $ReportReceiver["content"][1]["content"][1];
      if ($ReceiverTown !== null && isset($ReceiverTown['attributes']['href'])) {
        $LinkDataEncoded = $ReceiverTown['attributes']['href'];
        $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
        $ReceiverTownId = $aLinkData['id'];
        $ReceiverTownName = $aLinkData['name'];
      } else {
        throw new InboxParserExceptionWarning("receiver town not found in report");
      }

      // Player
      $ReceiverPlayer = $ReportReceiver["content"][3]["content"][1] ?? null;
      if ($ReceiverPlayer == null || (isset($ReceiverPlayer['type']) && $ReceiverPlayer['type'] == 'SPAM')) {
        $ReceiverPlayer = $ReportReceiver["content"][3]["content"][2] ?? null;
      }
      if ($ReceiverPlayer !== null && isset($ReceiverPlayer['attributes']['href'])) {
        $LinkDataEncoded = $ReceiverPlayer['attributes']['href'];
        $aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
        $ReceiverPlayerId = $aLinkData['id'];
        $ReceiverPlayerName = $aLinkData['name'];
      } else if (is_string($ReportReceiver["content"][3]['content'][0]) && $ReportReceiver["content"][3]['attributes']['class'] === 'town_owner') {
        $ReceiverPlayerId = 0;
        $ReceiverPlayerName = 'Ghost';
        $bReceiverIsGhost = true;
        // At this point, this can be a friendly attack on a ghost town or an enemy attack on a friendly conquest of a ghost town
      } else {
        throw new InboxParserExceptionWarning("receiver player not found in report");
      }

      // Alliance
      $ReceiverAllianceId = 0;
      $ReceiverAllianceName = '';
      $ReceiverAlliance = $ReportReceiver["content"][5]["content"] ?? null;
      if ($ReceiverAlliance !== null && is_array($ReceiverAlliance)) {
        foreach ($ReceiverAlliance as $aContent) {
          if (isset($aContent['attributes']['onclick']) && strpos($aContent['attributes']['onclick'], 'allianceProfile') !== false) {
            $Onclick = $aContent['attributes']['onclick'];
            if (strpos($Onclick, 'allianceProfile') !== false) {
              $OnclickSub = substr($Onclick, strrpos($Onclick, "',") + 2);
              $ReceiverAllianceId = substr($OnclickSub, 0, strlen($OnclickSub) - 1);
              if (isset($aContent['content'][0]) && is_string($aContent['content'][0])) {
                $ReceiverAllianceName = $aContent['content'][0];
              }
            }
          }
        }
      }
      if (($ReceiverAllianceId === 0 || !is_numeric($ReceiverAllianceId)) && !is_null($ReceiverPlayerId) && is_numeric($ReceiverPlayerId)) {
        $oPlayer = Player::firstById($World, $ReceiverPlayerId);
        if ($oPlayer !== null) {
          $ReceiverAllianceId = $oPlayer->alliance_id;
          if ($oPlayer->alliance_id > 0) {
            $oAlliance = Alliance::first($oPlayer->alliance_id, $World);
            if (!empty($oAlliance)) {
              $ReceiverAllianceName = $oAlliance->name;
            }
          }
        } else {
          $ReceiverAllianceId = 0;
        }
      }

      if ($PosterId === $ReceiverPlayerId || $ReportPoster === $ReceiverPlayerName || $bWisdom == true) {
        $bPlayerIsReceiver = true;
      }

      // === Report content
      $ReportType = "friendly_attack";
      $aBuildings = array();
      $aLandUnits = array();
      $aSeaUnits = array();
      $aMythUnits = array();
      $bIsOngoingConquest = false;
      $oConquestDetails = null;

      if ($bWisdom == true) {
        $ReportType = "wisdom";

        // Troop intel
        if (!isset($aSpyScript)||$aSpyScript==null) {
          // might be needed but probably not, throw error for now
          throw new InboxParserExceptionWarning("Inbox parser found no simulator script to retrieve units from wisdom.");
        }

        $aUnits = substr($aSpyScript, strpos($aSpyScript, '{"def":[],"att":{'));
        $aUnits = substr($aUnits, strpos($aUnits, ',"att":{'));
        $aUnits = '{' . substr($aUnits, 1, strpos($aUnits, '},"')) . '}';
        $aUnits = json_decode($aUnits, true);

        if (is_null($aUnits) || !key_exists('att', $aUnits) || !is_array($aUnits['att']) || sizeof($aUnits['att']) <= 0) {
          // Failed parsing units
          throw new InboxParserExceptionWarning("failed parsing wisdom units");
        }

        $bHasUnits = false;
        foreach ($aUnits['att'] as $Unit => $Value) {
          if ($Value <= 0) {
            continue;
          }
          $bHasUnits = true;
          if ($Unit == self::fireships) {
            $Fireships = $Value;
          } else if (in_array($Unit, self::land_units)) {
            $aLandUnits[$Unit] = $Value;
          } elseif (in_array($Unit, self::sea_units)) {
            $aSeaUnits[$Unit] = $Value;
          } elseif (in_array($Unit, self::heros)) {
            $Hero = $Unit;
          } else {
            foreach (self::myth_units as $UnitGod => $Units) {
              if (in_array($Unit, $Units)) {
                $God = $UnitGod;
                $aMythUnits[$Unit] = $Value;
              }
            }
          }
        }

        if ($bHasUnits == false) {
          // Failed parsing units
          throw new InboxParserExceptionWarning("No units found in wisdom script");
        }

      }
      else if (!$bSpy && !$bSupport) {
        if ($bPlayerIsReceiver) {
          $ReportType = "enemy_attack";
        }

        // Troop intel
        $ReportScript = $aReportData["content"][1]["content"][19]["content"][7]["content"][0];
        $ReportScript = str_replace("\t", "", $ReportScript);
        $ReportScript = str_replace("\n", "", $ReportScript);
        $ReportScript = str_replace("\"", '"', $ReportScript);

        $report_units_json = substr($ReportScript, strpos($ReportScript, "ReportViewer.dates["));
        $report_units_json = substr($report_units_json, strpos($report_units_json, '{"result":{'));
        $report_units_json = substr($report_units_json, 0, strpos($report_units_json, "ReportViewer.dates[")-1);
        $report_units_json = trim($report_units_json);
        if (substr($report_units_json, -1) == ';') {
          $report_units_json = substr($report_units_json, 0, -1);
        }

        $aUnits = json_decode($report_units_json, true);
        if ($aUnits == null || $aUnits == false || !isset($aUnits['result'])) {
          throw new InboxParserExceptionWarning("unable to parse units");
        }
        $aCityUnits = $aUnits['result'];
        $aCityUnitsDef = $aCityUnits['def_units'];
        $aCityUnitsAtt = $aCityUnits['att_units'];
        $aShowDefenderUnits = $aUnits['show_defender_units'];

        // Check if anything is visible
        $bGroundVisible = true;
        $bSeaVisible = true;
        if ($bPlayerIsSender && isset($aShowDefenderUnits)) {
          if (isset($aShowDefenderUnits['ground']) && $aShowDefenderUnits['ground']===false) {
            $aLandUnits = array("unknown" => "?");
            $bGroundVisible = false;
          }
          if (isset($aShowDefenderUnits['naval']) && $aShowDefenderUnits['naval']===false) {
            $aSeaUnits = array("unknown_naval" => "?");
            $bSeaVisible = false;
          }
        }

        if ((!$bPlayerIsSender && $bPlayerIsReceiver) || (!$bPlayerIsSender && !$bPlayerIsReceiver) || ($bPlayerIsReceiver && $bPlayerIsSender) || (!$bPlayerIsSender && $bReceiverIsGhost)) {
          // if owner can not be found, or if player is receiver, it could be an attack on conquest: check if there is a cs in defender units
          if (strpos(json_encode($aCityUnitsDef),self::kolo) !== false) {
            if ((!$bPlayerIsSender && $bPlayerIsReceiver)) {
              Logger::warning("Check003 InboxParser ".$ReportHash);
              // Can be attack on conquest but can also be a normal enemy attack where playerIsReceiver and where player is defending with a cs
            } else {
              $ReportType = "attack_on_conquest";
            }
            $bPlayerIsReceiver = true;
            $bIsOngoingConquest = true;

            // Parse conquests
            $oConquestDetails = new ConquestDetails();
            try {
              // try to parse conquest details
              $oConquestDetails->siegeTownId = $ReceiverTownId;
              $oConquestDetails->siegeTownName = $ReceiverTownName;
              $oConquestDetails->siegePlayerId = $PosterId;
              $oConquestDetails->siegePlayerName = $ReportPoster;
            } catch (\Exception $e) {
              Logger::warning("InboxParser $ReportHash: error parsing ongoing conquest details; " . $e->getMessage());
            }
          } else if ((!$bPlayerIsSender && !$bPlayerIsReceiver) || ($bPlayerIsReceiver && $bPlayerIsSender) || (!$bPlayerIsSender && $bReceiverIsGhost)) {
            // unable to identify owner!
            throw new InboxParserExceptionWarning("inbox report owner not found");
          }
        }

        if ($bPlayerIsSender && $bPlayerIsReceiver && $bIsOngoingConquest) {
          // Attacking an enemy conquest on their own city. parse own units
          Logger::warning("Check002 InboxParser ".$ReportHash);
          $aCityUnits = $aCityUnitsAtt;
        } elseif ($bPlayerIsSender) {
          $aCityUnits = $aCityUnitsDef;
        } else {
          $aCityUnits = $aCityUnitsAtt;
        }

        // Check if any were lost
        if (sizeof($aCityUnits) <= 0 && ($bGroundVisible || $bSeaVisible)) {
          throw new InboxParserExceptionWarning("unable to find report units");
        }

        // Normal att OR defender side
        $aUnitsClean = self::parseSingleSideUnits($aCityUnits, $ReportHash);
        foreach ($aUnitsClean as $Unit => $Value) {
          if ($bSeaVisible && $Unit == self::fireships) {
            $Fireships = $Value;
          } else if ($bGroundVisible && in_array($Unit, self::land_units)) {
            $aLandUnits[$Unit] = $Value;
          } elseif ($bSeaVisible && in_array($Unit, self::sea_units)) {
            $aSeaUnits[$Unit] = $Value;
          } elseif ($bGroundVisible && in_array($Unit, self::heros)) {
            $Hero = $Unit;
          } else if ($bGroundVisible || ($bSeaVisible && in_array($Unit, self::myth_sea_units))) {
            foreach (self::myth_units as $UnitGod => $Units) {
              if (in_array($Unit, $Units)) {
                $God = $UnitGod;
                $aMythUnits[$Unit] = $Value;
              }
            }
          }
        }

        // Parse conquest side
        if ($bIsOngoingConquest == true && !is_null($oConquestDetails)) {
          $aUnitsClean = self::parseSingleSideUnits($aCityUnitsDef, $ReportHash);
          $oConquestDetails->siegeUnits = $aUnitsClean;
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

        // Buildings
        $aBuildings = array('wall' => 0);

        try {
          $aReportDetails = $aReportData["content"][1]["content"][19]["content"][5]["content"][1]["content"][5]['content'];
          foreach ($aReportDetails as $aDetails) {
            $Class = $aDetails['attributes']['class'] ?? null;
            if ($Class == null || !is_string($Class)) continue;

            if (strpos($Class, 'oldwall') !== false) {
              $DetailClass = $aDetails['content'][0]['attributes']['class'] ?? null;
              if ($DetailClass == null || !is_string($DetailClass)) continue;

              $ValueString = $aDetails["content"][1] ?? null;
              if ($ValueString == null || !is_string($ValueString)) {
                Logger::warning("InboxParser $ReportHash: unable to find value for report building");
                continue;
              }

              if (strpos($ValueString, '+100') !== false) {
                // ignore night bonus
                continue;
              }

              $Value = substr($ValueString, strpos($ValueString, ": ") + 2);
              $Value = substr($Value, 0, strpos($Value, ")") + 1);
              if (strpos($Value, '+') !== false
                || strpos($Value, '%') !== false
                || strlen($Value) <= 0
                || (strlen($Value) > 2 && strpos($Value, '(') === false)
              ) {
                Logger::warning("InboxParser $ReportHash: invalid value for report building");
                continue;
              }

              if (strpos($DetailClass, 'catapult') !== false) {
                // wall
                $aBuildings['wall'] = $Value;
              } else if (strpos($DetailClass, 'stone_hail') !== false && $aBuildingNames != null) {
                foreach ($aBuildingNames as $Key => $Name) {
                  if (strpos(strtolower($ValueString), strtolower($Name)) !== false) {
                    $aBuildings[$Key] = $Value;
                    continue 2;
                  }
                }
              } else {
                // First instance must be wall, break loop
                $aBuildings['wall'] = $Value;
                break;
              }

            }
          }
        } catch (\Exception $e) {
          throw new InboxParserExceptionWarning("Error parsing attack buildings: " . $e->getMessage());
        }

        if ($bIsOngoingConquest && !is_null($oConquestDetails)) {
          $oConquestDetails->wall = $aBuildings['wall'] ?? 0;
        }

        if ($bPlayerIsReceiver) {
          // Wall is only known if enemy is defender
          $aBuildings = array();
        }

        // Try to parse lost unknown units from battle points gain
        if ($bPlayerIsSender && (!$bGroundVisible || !$bSeaVisible)) {
          try {
            // Parse BP gain
            $KillPointsElement = Helper::allById($aReportData, 'kill_points', true);
            if (count($KillPointsElement)!==1) {
              throw new Exception("Unable to find unique kill points element in friendly attack report");
            }
            $GainedBattlePoints = Helper::allByClass($KillPointsElement[0], 'battle_points', true);
            if (count($GainedBattlePoints)===1) {
              // BP gained
              $GainedBattlePoints = Helper::getTextContent($GainedBattlePoints[0]);
              preg_match_all('!\d+!', $GainedBattlePoints, $matches);
              if (count($matches)!=1) {
                throw new Exception("Unable to find unique battle points integer");
              }
              $GainedBattlePoints = $matches[0][0];
            } else {
              // no BP gained but kill points element exists. assume BP gain = 0
              $GainedBattlePoints = 0;
            }

            // Parse boosts (BP boosts should be subtracted from BP gain)
            $CombatExperienceBoost = .1;
            $LandBoostFactor = 1;
            $SeaBoostFactor = 1;
            $TsCsBoostFactor = 1; // Sea boost can be ignored for transport and cs ships with new boosters
            if (count(Helper::allByClass($aReportData, 'divine_senses')) >= 1) {
              // old 300% boost for land+sea (x4)
              $CombatExperienceBoost += .3;
              $LandBoostFactor += 3;
              $SeaBoostFactor += 3;
              $TsCsBoostFactor += 3;
            } else if (
              (count(Helper::allByClass($aReportData, 'acumen')) === 1
              && count(Helper::allByClass($aReportData, 'assassins_acumen')) === 0)
              || (count(Helper::allByClass($aReportData, 'acumen')) === 2)
            ) {
              // old 100% boost for land+sea (x2)
              $CombatExperienceBoost += .1;
              $LandBoostFactor += 1;
              $SeaBoostFactor += 1;
              $TsCsBoostFactor += 1;
            } else if (count(Helper::allByClass($aReportData, 'divine_battle_strategy_epic')) >= 1) {
              // 100% boost for land+sea (x2)
              $CombatExperienceBoost += .1;
              $LandBoostFactor += 1;
              $SeaBoostFactor += 1;
            } else if (count(Helper::allByClass($aReportData, 'divine_battle_strategy_rare')) >= 1) {
              // 50% boost for land+sea
              $CombatExperienceBoost += .05;
              $LandBoostFactor += .5;
              $SeaBoostFactor += .5;
            } else {
              if (count(Helper::allByClass($aReportData, 'land_battle_strategy_epic')) >= 1) {
                // 100% boost for land
                $LandBoostFactor += 1;
              } else if (count(Helper::allByClass($aReportData, 'land_battle_strategy_rare')) >= 1) {
                // 50% boost for land
                $LandBoostFactor += .5;
              }

              if (count(Helper::allByClass($aReportData, 'naval_battle_strategy_epic')) >= 1) {
                // 100% boost for sea
                $SeaBoostFactor += 1;
              } else if (count(Helper::allByClass($aReportData, 'naval_battle_strategy_rare')) >= 1) {
                // 50% boost for sea
                $SeaBoostFactor += .5;
              }
            }

            // Only event boosts stack with regular boosts
            $CommunityBoosts = 0;
            if (count(Helper::allByClass($aReportData, 'assassins_acumen')) >= 1) {
              // 50% community boost
              $CombatExperienceBoost += .05;
              $CommunityBoosts += .5;
            } else if (count(Helper::allByClass($aReportData, 'missions_power_4')) >= 1) {
              // 50% pandora event boost
              $CombatExperienceBoost += .05;
              $CommunityBoosts += .5;
            } else {
              $OlympicSenses = Helper::allByClass($aReportData, 'olympic_senses');
              if (count($OlympicSenses) === 1) {
                // 10-20-30-40% olympic boost
                $OlympicSenses = $OlympicSenses[0];
                if (strpos($OlympicSenses['attributes']['class'], 'lvl1')!==false) {
                  $CommunityBoosts += .1;
                } else if (strpos($OlympicSenses['attributes']['class'], 'lvl2')!==false) {
                  $CommunityBoosts += .2;
                } else if (strpos($OlympicSenses['attributes']['class'], 'lvl3')!==false) {
                  $CommunityBoosts += .3;
                } else if (strpos($OlympicSenses['attributes']['class'], 'lvl4')!==false) {
                  $CommunityBoosts += .4;
                }
              }
            }
            $LandBoostFactor += $CommunityBoosts;
            $SeaBoostFactor += $CommunityBoosts;
            $TsCsBoostFactor += $CommunityBoosts;
            $CombatExperienceBoost += ($CommunityBoosts/10);

            // Subtract combat experience from gained battle points
            if ($bAttackerHasCombatExperience) {
              $AttackerUnitKillCount = self::parseUnitKillCount($aCityUnitsAtt, false, false);
              $GainedBattlePoints -= ceil($CombatExperienceBoost * $AttackerUnitKillCount);
            }

            // Apply the calculated BP gains
            $LandAttackPercentage = self::parseLandUnitPercentage($aCityUnitsAtt);
            $AirAttackPercentage = self::parseLandUnitPercentage($aCityUnitsAtt, true);
            if ($bSeaVisible && !$bGroundVisible) {
              if ($LandAttackPercentage>0 && $GainedBattlePoints>=0) {
                // Sea is visible, subtract any dead sea units from BG gain and then assume remainder is subtracted from land units
                $SeaUnitKillCountTotal = self::parseUnitKillCount($aCityUnitsDef, true, false);
                $SeaUnitKillCountRegular = self::parseUnitKillCount($aCityUnitsDef, true, true);
                $SeaUnitKillCountTsCs = $SeaUnitKillCountTotal - $SeaUnitKillCountRegular;
                $LandBPGain = $GainedBattlePoints
                  - ($SeaUnitKillCountTsCs * $TsCsBoostFactor)
                  - ($SeaUnitKillCountRegular * $SeaBoostFactor);
                $LandBPGain = (int) floor($LandBPGain / $LandBoostFactor);
                $aLandUnits = array("unknown" => "?(-$LandBPGain)");
              }
            } else if (!$bSeaVisible && !$bGroundVisible) {
              // Sea and land are not visible, apply BP loss using attacker unit distribution
              $LandBPGainBruto = (int) floor($GainedBattlePoints * $LandAttackPercentage); // How much of the BP was gained by land units
              $LandBPGainByAir = (int) floor(($LandBPGainBruto / $LandBoostFactor) * $AirAttackPercentage); // How much of the BP was gained by myth units

              if ($LandAttackPercentage != 1) {
                // Any remaining land BP was gained by non-mythical air units.
                // Land boost is greatly diminished due to naval units not being cleared and this being a naval attack
                $LandBoostFactor *= 10;
                if (($LandBPGainBruto - $LandBPGainByAir) > 0) {
                  Logger::warning("Check001: $ReportHash");
                }
              }
              $LandBPGainByLand = (int) floor(($LandBPGainBruto - $LandBPGainByAir) / $LandBoostFactor);

              $LandBPGainNetto = $LandBPGainByAir + $LandBPGainByLand;
              $SeaBPGain = (int) floor(($GainedBattlePoints - $LandBPGainBruto) / $SeaBoostFactor);
              $aLandUnits = array("unknown" => "?(-$LandBPGainNetto)");
              $aSeaUnits = array("unknown_naval" => "?(-$SeaBPGain)");
            }
          } catch (Exception $e) {
            Logger::warning("InboxParser $ReportHash: Error parsing battle points for unknown unit loss; ".$e->getMessage());
          }
        }

      } else if ($bSupport === true) {
        $ReportType = "support";
        $bMatched = false;
        $InvalidClassCount = 0;

        //loop units
        $unitsRootArr = $aReportData["content"][1]["content"][19]["content"][5]["content"];
        foreach ($unitsRootArr as $unitsChild) {
          if(is_array($unitsChild)
            && count($unitsChild)>1
            && isset($unitsChild['attributes']['class'])) {
            foreach (self::land_units as $unit) {
              if (strpos($unitsChild['attributes']['class'], "unit_".$unit)) {
                $bMatched = true;
                $aLandUnits[$unit] = $unitsChild['content'][1]['content'][0];
              }
            }
            foreach (self::sea_units as $unit) {
              if (strpos($unitsChild['attributes']['class'], "unit_".$unit)) {
                $bMatched = true;
                if ($unit === self::fireships) {
                  $Fireships = $unitsChild['content'][1]['content'][0];
                } else {
                  $aSeaUnits[$unit] = $unitsChild['content'][1]['content'][0];
                }
              }
            }
            foreach (self::myth_units as $UnitGod => $Units) {
              foreach ($Units as $unit) {
                if (strpos($unitsChild['attributes']['class'], "unit_".$unit)) {
                  $bMatched = true;
                  $aMythUnits[$unit] = $unitsChild['content'][1]['content'][0];
                  $God = $UnitGod;
                }
              }
            }
            if (in_array($unitsChild['attributes']['class'], ['gp_town_link', 'gp_player_link'])) {
              $InvalidClassCount++;
            }
          }
        }

        if ($bMatched === false) {
          if ($InvalidClassCount === 3 || $InvalidClassCount === 2) {
            throw new InboxParserExceptionDebug("Troops can not support town with invalid god");
          } else {
            throw new InboxParserExceptionWarning("Unable to match any support troops");
          }
        }
      } else if ($bSpy === true) {
        // Handle spy report
        $ReportType = "spy";

        if ((!$bPlayerIsSender && !$bPlayerIsReceiver) || ($bPlayerIsReceiver && $bPlayerIsSender)) {
          // unable to identify owner!
          throw new InboxParserExceptionWarning("inbox spy report owner not found");
        }

        $LeftSide = null;
        $RightSide = null;
        foreach ($aReportData["content"][1]["content"][19]["content"] as $aElement) {
          if (is_array($aElement) && key_exists('attributes', $aElement) && key_exists('id', $aElement['attributes'])) {
            if ($aElement['attributes']['id'] === 'left_side') {
              $LeftSide = $aElement;
            }
            else if ($aElement['attributes']['id'] === 'right_side') {
              $RightSide = $aElement;
            }
          }
        }

        if (!key_exists('content', $LeftSide) || sizeof($LeftSide['content'])<=3) {
          // Spy failed, not enough silver
          throw new InboxParserExceptionDebug("spy failed; not enough silver");
        }

        $aRightSideItems = Helper::allByClass($RightSide, 'spy_success_left_align');

        $Silver = '?';
        if (isset($aRightSideItems[0]) && is_array($aRightSideItems[0])) {
          $Silver = Helper::getTextContent($aRightSideItems[0], 0, true) ?? '?';
          $Silver = preg_replace('/\s+/', '', $Silver);
        }

        $GodMicro = Helper::allByClass($RightSide, 'god_micro');
        $bFoundGod = false;
        if (isset($GodMicro[0]) && is_array($GodMicro[0])) {
          $God = strtolower($GodMicro[0]['attributes']['title']) ?? null;
          if (!key_exists($God, self::myth_units)) {
            foreach (array_keys(self::myth_units) as $GodName) {
              if (strpos($GodMicro[0]['attributes']['class'], $GodName)!==false) {
                $God = $GodName;
                $bFoundGod = true;
              }
            }
          } else {
            $bFoundGod = true;
          }
          if ($God == '') $God = null;
          if (!$bFoundGod) {
            Logger::warning("InboxParser $ReportHash: unable to parse god");
          }
        }

        if (!isset($aSpyScript)||$aSpyScript==null) {
          // might be needed but probably not, throw error for now
          throw new InboxParserExceptionWarning("Inbox parser found no spyscript to retrieve units.");
        }

        if (strpos($aSpyScript, 'sim_units = {"def":[]')===false) {
          $aUnits = substr($aSpyScript, strpos($aSpyScript, 'sim_units = {"def":{'));
          $aUnits = substr($aUnits, strpos($aUnits, '{"def":{'));
          $aUnits = substr($aUnits, 0, strpos($aUnits, '}};')+2);
          $aUnits = json_decode($aUnits, true);

          if (is_null($aUnits) || !key_exists('def', $aUnits)) {
            // Failed parsing units
            throw new InboxParserExceptionWarning("failed parsing spy units");
          }

          foreach ($aUnits['def'] as $Unit => $Value) {
            if ($Unit == self::fireships) {
              $Fireships = $Value;
            } else if (in_array($Unit, self::land_units)) {
              $aLandUnits[$Unit] = $Value;
            } elseif (in_array($Unit, self::sea_units)) {
              $aSeaUnits[$Unit] = $Value;
            } elseif (in_array($Unit, self::heros)) {
              $Hero = $Unit;
            } else {
              foreach (self::myth_units as $UnitGod => $Units) {
                if (in_array($Unit, $Units)) {
                  $God = $UnitGod;
                  $aMythUnits[$Unit] = $Value;
                }
              }
            }
          }
        }

        // Buildings
        $bHasWall = false;
        foreach ($LeftSide["content"] as $Element) {
          if (is_array($Element)
            && key_exists('attributes', $Element)
            && key_exists('id', $Element['attributes'])
            && $Element['attributes']['id'] == 'spy_buildings') {
            foreach ($Element["content"] as $aBuildingElement) {
              if (is_array($aBuildingElement)
                && key_exists('attributes', $aBuildingElement)
                && key_exists('class', $aBuildingElement['attributes'])
                && $aBuildingElement['attributes']['class'] == 'spy_success_left_align') {
                foreach ($aBuildingElement["content"] as $Building) {
                  if (is_array($Building) && key_exists('attributes', $Building)) {
                    $Name = $Building['attributes']['class'];
                    $Name = substr($Name, strlen("report_unit building_"));
                    $Level = $Building["content"][1]["content"][0];
                    if ($Name === 'wall') {
                      $bHasWall = true;
                    }
                    if (!is_string($Level)) {
                      $Level = Helper::getTextContent($Level);
                    }
                    $aBuildings[$Name] = $Level;
                  }
                }
              }
            }
          }
        }
        if ($bHasWall === false) {
          $aBuildings["wall"] = "0";
        }
      } else {
        throw new InboxParserExceptionError("Unmatched report type");
      }

      if ((!$bPlayerIsSender && !$bPlayerIsReceiver) || ($bPlayerIsReceiver && $bPlayerIsSender)) {
        if ($bIsOngoingConquest) {
          // throw new InboxParserExceptionDebug("Ignoring friendly attack on conquest");
          // friendly attack on conquest, continue with indexing.
        } else if ($bPlayerIsReceiver && $bPlayerIsSender) {
          throw new InboxParserExceptionDebug("Sender and receiver are the same. Ignore report");
        } else {
          // unable to identify owner!
          throw new InboxParserExceptionWarning("Inbox parser unable to identify report owner!");
        }
      }

      if ($bWisdom == true || ($bPlayerIsSender == true && !$bIsOngoingConquest)) {
        $TownId = $ReceiverTownId;
        $TownName = $ReceiverTownName;
        $PlayerId = $ReceiverPlayerId;
        $PlayerName = $ReceiverPlayerName;
        $AllianceId = $ReceiverAllianceId;
      } else {
        $TownId = $SenderTownId;
        $TownName = $SenderTownName;
        $PlayerId = $SenderPlayerId;
        $PlayerName = $SenderPlayerName;
        $AllianceId = $SenderAllianceId;
      }

      if ($TownId===null || $PlayerId===null){
        throw new InboxParserExceptionWarning("Town or player are null! player is: ".($bPlayerIsSender?'sender':'receiver'));
      }

      // Parse luck
      // Luck is used to enforce uniqueness between inbox and forum intel (same report from different source should be blocked)
      $Luck = 0;
      if (in_array($ReportType, array('attack_on_conquest', 'friendly_attack', 'enemy_attack'))) {
        try {
          $aLuckElement = Helper::allByClass($aReportData, 'fight_bonus luck');
          if (count($aLuckElement)!==1) {
            throw new Exception("found an invalid number of luck elements");
          }
          $LuckElement = $aLuckElement[0];

          $LuckText = Helper::getTextContent($LuckElement);
          $FoundMatches = preg_match_all('/-?\d{1,3} ?%/m', $LuckText, $aPercentages, PREG_SET_ORDER, 0);

          if (1 !== $FoundMatches) {
            throw new Exception("Expected 1 match, found $FoundMatches matches");
          }

          $LuckMatch = $aPercentages[0][0];
          $LuckMatch = str_replace(' ', '', $LuckMatch);
          $LuckMatch = str_replace('%', '', $LuckMatch);

          if (!is_numeric($LuckMatch)) {
            throw new Exception("found non numeric luck value");
          }
          if ($LuckMatch<-30 || $LuckMatch>30) {
            throw new Exception("found an out of bounds luck value");
          }

          $Luck = $LuckMatch;
        } catch (Exception $e) {
          Logger::warning("Inbox parser $ReportHash: Error parsing luck; ".$e->getMessage()." [".$e->getTraceAsString()."]");
        }
      }


      // Save Results
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
      $oIntel->luck        = $Luck??0;
      $oIntel->world       = $World;
      $oIntel->v1_index    = $World; // This field is only used to allow duplicate entries of V1 intel, new reports should not use this field but it can also not be null otherwise SQL wont enforce the unique index.
      $oIntel->source_type = 'inbox';
      $oIntel->report_type = $ReportType;
      $oIntel->script_version = $ScriptVersion;

      $oIntel->town_id     = $TownId;
      $oIntel->town_name   = $TownName;
      $oIntel->player_id   = $PlayerId;
      $oIntel->player_name = $PlayerName;
      $oIntel->alliance_id = $AllianceId;
      $oIntel->poster_player_name = $ReportPoster;
      $oIntel->poster_player_id   = $PosterId;
      $oIntel->poster_alliance_id = $PosterAllyId;
      $oIntel->report_date = $ReportDate;
      $oIntel->parsed_date = $ParsedDate;
      $oIntel->hero        = (isset($Hero)?$Hero:null);
      $oIntel->god         = (isset($God)?$God:null);
      $oIntel->silver      = (isset($Silver)?$Silver:null);
      $oIntel->buildings   = json_encode($aBuildings);
      $oIntel->land_units  = json_encode($aLandUnits);
      $oIntel->sea_units   = json_encode($aSeaUnits);
      if (isset($Fireships)) {$oIntel->fireships = $Fireships;}
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
          $ParsedDate->addHour();
          $oIntel->parsed_date = $ParsedDate;
          $bSaved = $oIntel->save();
        } else {
          throw new \Exception("Unable to save City record with error: " . $e->getMessage());
        }
      }

      if ($bSaved == false || $oIntel->id < 0) {
        throw new InboxParserExceptionWarning("Unable to save City record: " . $oIntel->toJson());
      }

      // Check conquest
      try {
        if ($bIsOngoingConquest == true && !is_null($oConquestDetails)) {
          $oIntel->conquest_details = json_encode($oConquestDetails->jsonSerialize());
          $SiegeId = SiegeParser::saveSiegeAttack($oConquestDetails, $oIntel, $aIndexes, $ReportHash,
            array('player_id' => $ReceiverPlayerId, 'player_name' => $ReceiverPlayerName,
              'alliance_id' => $ReceiverAllianceId, 'alliance_name' => $ReceiverAllianceName));
          if (!empty($SiegeId) && !is_nan($SiegeId) && $SiegeId > 0) {
            $oIntel->conquest_id = $SiegeId;
            $oIntel->save();
          }
        }
      } catch (Exception $e) {
        Logger::warning("InboxParser $ReportHash: unable to update conquest details after creating city object; " . $e->getMessage());
      }

      return $oIntel->id;
    }
    catch(InboxParserExceptionDebug $e) {throw $e;}
    catch(InboxParserExceptionWarning $e) {throw $e;}
    catch(InboxParserExceptionError $e) {throw $e;}
    catch (\Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // try to find duplicate intel id
        try {
          $oIntel = Intel::where('town_id', '=', $TownId)
            ->where('world', '=', $World)
            ->where('parsed_date', '=', $ParsedDate)
            ->where('report_type', '=', $ReportType)
            ->where('luck', '=', ($Luck??0))
            ->firstOrFail();
          return $oIntel->id;
        } catch (\Exception $e) {
          throw new InboxParserExceptionWarning("Unable to find duplicate intel entry");
        }
      } else {
        throw new InboxParserExceptionError("Uncaught exception in inbox parser: " . $e->getMessage() . '. [' . $e->getTraceAsString() . ']');
      }
    }

  }

  private static function parseSingleSideUnits($aCityUnits, $ReportHash)
  {
    $aCityUnitsFinal = array(
      'had' => array(),
      'lost' => array()
    );
    foreach ($aCityUnits as $aUnitInfo) {
      foreach (['had','lost'] as $Moment) {
        if (key_exists($Moment, $aUnitInfo) && sizeof($aUnitInfo[$Moment]) > 0) {
          foreach ($aUnitInfo[$Moment] as $Unit => $Value) {
            if (!key_exists($Unit, $aCityUnitsFinal[$Moment])) {
              $aCityUnitsFinal[$Moment][$Unit] = $Value;
            } else if ($Moment=='lost') {
              $aCityUnitsFinal[$Moment][$Unit] += $Value;
            }
          }
        }
      }
    }
    $aCityUnits = $aCityUnitsFinal;

    $aUnitsClean = array();
    foreach ($aCityUnits['had'] as $Unit => $Value) {
      if ($Unit == 'ghosts' && is_array($Value) && count($Value) === 1) {
        // parse spartoi
        $Unit = array_keys($Value)[0];
        $Value = $Value[$Unit];
      }
      if (is_array($Value)) {
        Logger::warning("InboxParser: TODO check: Unit array to string conversion: ".$ReportHash);
      }

      $aUnitsClean[$Unit] = $Value;
    }
    foreach ($aCityUnits['lost'] as $Unit => $Value) {
      if ($Unit == 'ghosts' && is_array($Value) && count($Value) === 1) {
        // parse spartoi
        $Unit = array_keys($Value)[0];
        $Value = $Value[$Unit];
      }
      if (is_array($Value)) {
        Logger::warning("InboxParser: TODO check: Unit array to string conversion: ".$ReportHash);
      }
      if (isset($aUnitsClean[$Unit])) {
        $aUnitsClean[$Unit] = $aUnitsClean[$Unit] . '(-'.$Value.')';
      } else {
        $aUnitsClean[$Unit] = $Value . '(-'.$Value.')';
      }
    }
    return $aUnitsClean;
  }

  /**
   * Sums the population kill count for all units in the input
   * @param $aCityUnits
   * @param bool $bIgnoreLandUnits
   * @param bool $bIgnoreTransportAndCS
   * @return float|int
   */
  private static function parseUnitKillCount($aCityUnits, $bIgnoreLandUnits = true, $bIgnoreTransportAndCS = true)
  {
    $KillCount = 0;
    foreach ($aCityUnits as $aUnitInfo) {
      if (key_exists('lost', $aUnitInfo) && sizeof($aUnitInfo['lost']) > 0) {
        foreach ($aUnitInfo['lost'] as $Unit => $Value) {
          if (!key_exists($Unit, UnitStats::units)) {
            continue;
          }
          if (UnitStats::units[$Unit]['uses_cartography']) {
            // sea units
            if (in_array($Unit, array('colonize_ship', 'small_transporter', 'big_transporter'))) {
              // transport or cs
              if (!$bIgnoreTransportAndCS) {
                $KillCount += $Value * UnitStats::units[$Unit]['population'];
              }
            } else {
              // regular
              $KillCount += $Value * UnitStats::units[$Unit]['population'];
            }
          } else {
            // land units
            if (!$bIgnoreLandUnits) {
              $KillCount += $Value * UnitStats::units[$Unit]['population'];
            }
          }
        }
      }
    }
    return $KillCount;
  }

  /**
   * returns a float between 0 and 1 that indicates relatively how much of the attackers units are land units
   * transport and cs units are not counted towards sea population
   * @param $aCityUnits
   * @param bool $bOnlyParseAirAttack If set to true, only mythical units attacking via air are counted
   * @return float|int
   */
  private static function parseLandUnitPercentage($aCityUnits, $bOnlyParseAirAttack = false)
  {
    $SeaAttackPopulation = 0;
    $LandAttackPopulation = 0;
    $LandPopOther = 0;
    $aUnitInfo = $aCityUnits[array_keys($aCityUnits)[0]]; // first round only
    if (key_exists('had', $aUnitInfo) && sizeof($aUnitInfo['had']) > 0) {
      foreach ($aUnitInfo['had'] as $Unit => $Value) {
        if (UnitStats::units[$Unit]['uses_cartography']) {
          if (!in_array($Unit, array('colonize_ship', 'small_transporter', 'big_transporter'))) {
            $SeaAttackPopulation += $Value * UnitStats::units[$Unit]['population'];
          }
        } else if (!$bOnlyParseAirAttack || (UnitStats::units[$Unit]['uses_meteorology'] === true && UnitStats::units[$Unit]['requires_transport'] === false)) {
          $LandAttackPopulation += $Value * UnitStats::units[$Unit]['population'];
        } else {
          $LandPopOther += $Value * UnitStats::units[$Unit]['population'];
        }
      }
    }
    return $LandAttackPopulation / ($LandAttackPopulation + $SeaAttackPopulation + $LandPopOther);
  }

}
