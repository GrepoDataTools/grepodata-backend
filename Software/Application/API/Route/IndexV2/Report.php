<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Intel;
use Grepodata\Library\Controller\IndexV2\IntelShared;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Exception\ForumParserExceptionDebug;
use Grepodata\Library\Exception\ForumParserExceptionError;
use Grepodata\Library\Exception\ForumParserExceptionWarning;
use Grepodata\Library\Exception\InboxParserExceptionDebug;
use Grepodata\Library\Exception\InboxParserExceptionError;
use Grepodata\Library\Exception\InboxParserExceptionWarning;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;

use Illuminate\Database\Capsule\Manager as DB;

class Report extends \Grepodata\Library\Router\BaseRoute
{
  public static function LatestReportHashesGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
      $World = $aParams['world'];
      $WorldEscaped = DB::connection()->getPdo()->quote($World);

      try {
        if ($oUser->userscript_active < Carbon::now()->subDays(3)) {
          $oUser->userscript_active = Carbon::now();
          $oUser->save();
        }
      } catch (\Exception $e) {
        Logger::warning("Error updating userscript flag: ".$e->getMessage());
      }

      // Get all active indexes for this user in this world
      $aIndexes = IndexInfo::allByUserAndWorld($oUser, $World);
      $aActiveIndexes = array();
      $aAllIndexes = array();
      $bIsContributingToTeam = false;
      foreach ($aIndexes as $oIndex) {
        $bIsContributingToTeam = true;
        if ($oIndex->role != Roles::ROLE_READ && $oIndex->contribute == true) {
          $aActiveIndexes[$oIndex->key_code] = $oIndex->key_code;
        }
        $aAllIndexes[] = array(
          'key'         => $oIndex->key_code,
          'name'        => $oIndex->index_name,
          'role'        => $oIndex->role,
          'contribute'  => $oIndex->contribute,
        );
      }

      // Number of recent hashes to return
      $Window = 250;

      // Get the latest n records that appear in all of these indexes or that were personally indexed by this user
      $KeyString = "'" . join("', '", array_keys($aActiveIndexes)) . "'";

        $aHashes = DB::select(DB::raw("
          SELECT report_hash, min(sort_id) as sort_id
            FROM (
                (SELECT report_hash, min(id) as sort_id
                 FROM Indexer_intel_shared
                 WHERE index_key IN (".$KeyString.")
                 GROUP BY report_hash
                 having COUNT(index_key) = ".count($aActiveIndexes).")
        
                UNION
        
                (SELECT report_hash, min(id) as sort_id
                 FROM Indexer_intel_shared
                 WHERE user_id = ".$oUser->id." and world = ".$WorldEscaped."
                 GROUP by report_hash)
            ) as hashlist
            GROUP BY report_hash
            ORDER BY sort_id DESC
            limit ".$Window."
        "));

      $aHashlist = array();
      $bHasHashes = false;
      foreach ($aHashes as $Hash) {
        $bHasHashes = true;
        $Hash = (array) $Hash;
        if (isset($Hash['report_hash'])) {
          $aHashlist[] = $Hash['report_hash'];
        }
      }

      $aResponse = array(
        'hashlist' => $aHashlist,
        'active_teams' => $aAllIndexes
      );

      die(self::OutputJson($aResponse, 200));
    } catch (\Exception $e){
      Logger::error('Error retrieving latest indexer hashes: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

  public static function indexReportPOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('report_type', 'access_token', 'world', 'report_hash', 'report_text', 'report_json', 'report_poster', 'report_poster_id', 'report_poster_ally_id', 'script_version'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      // Get data
      $ReportType = $aParams['report_type'];
      $ReportInfo = preg_replace('/\s+/', ' ', $aParams['report_text']);
      $ReportInfo = substr($ReportInfo, 0, 500);
      $ScriptVersion = $aParams['script_version'];
      $ReportRaw = $aParams['report_json'];
      $ReportJson = json_encode($ReportRaw);
      $ReportHash = $aParams['report_hash'];
      $ReportPoster = $aParams['report_poster'];
      $ReportPosterId = $aParams['report_poster_id'];
      $ReportPosterAllyId = $aParams['report_poster_ally_id'];
      $World = $aParams['world'];
      $Locale = substr($World, 0, 2);
      $bAttackerHasCombatExperience = isset($aParams['has_combat_experience']) && ($aParams['has_combat_experience'] === 'true' || $aParams['has_combat_experience'] === true);

      // Get indexes for player
      $aIndexes = IndexInfo::allByUserAndWorld($oUser, $World);
      $aRawIndexKeyList = array();
      foreach ($aIndexes as $oIndex) {
        if ($oIndex->key_code != null) {
          $aRawIndexKeyList[] = $oIndex->key_code;
        }
      }

      // Get all shared intel for this hash and user
      $aSharedIntel = IntelShared::allByHashByUser($oUser, $World, $ReportHash);

      if (sizeof($aSharedIntel) > 0) {
        // Hash was already parsed
        $IntelId = $aSharedIntel[0]->intel_id;

        // Update HTML for existing report
        try {
          $bSave = false;
          $oIntel = Intel::getById($IntelId);
          if ($oIntel->report_json == '' || $oIntel->report_json == null) {
            $oIntel->report_json = $ReportJson;
            $bSave = true;
          }
          if ($oIntel->report_info == '' || $oIntel->report_info == null) {
            $oIntel->report_info = json_encode(substr($ReportInfo, 0, 100));
            $bSave = true;
          }
          if ($bSave == true) {
            $oIntel->save();
          }
        } catch (\Exception $e) {
          Logger::warning("Error updating html for report hash: $ReportHash and user: ".$oUser->id.";" . $e->getMessage());
        }

        // Check if all indexes for this user have the hash, if not add the hash to the missing index
        $aIndexKeys = [];
        $bUserHasHash = false;
        foreach ($aSharedIntel as $oIntelShared) {
          if ($oIntelShared->user_id != null) {
            $bUserHasHash = true;
          } else {
            $aIndexKeys[] = $oIntelShared->index_key;
          }
        }
        if ($bUserHasHash == false) {
          // Add a shared record for this user (to indicate that this user ALSO indexed the report)
          IntelShared::saveHashToUser($ReportHash, $IntelId, $oUser, $World, $ReportPosterId ?? null);
        }
        foreach ($aIndexes as $oIndex) {
          if (!in_array($oIndex->key_code, $aIndexKeys)) {
            IntelShared::saveHashToIndex($ReportHash, $IntelId, $oIndex, $ReportPosterId ?? null);

            // Toggle new report switch on index
            if ($oIndex->new_report != 1) {
              $oIndex->new_report = 1;
              $oIndex->save();
            }
          }
        }

        die(self::OutputJson(array(), 200));
      } else {
        // This is new intel, parse it
        $IntelId = 0;
        $LogPrefix = 'Unable to parse report with hash ' . $ReportHash . ' [user '.$oUser->id.' - v'.$ScriptVersion.' - world '.$World.' - '.$ReportType.']';
        $Explain = null;
        $bParsingError = false;
        try {
          switch ($ReportType) {
            case 'inbox':
              $IntelId = \Grepodata\Library\IndexV2\InboxParser::ParseReport(
                $oUser->id,
                $World,
                $ReportRaw,
                $ReportPoster,
                $ReportPosterId,
                $ReportPosterAllyId,
                $bAttackerHasCombatExperience,
                $ReportHash,
                $ReportJson,
                $ReportInfo,
                $ScriptVersion,
                $Locale,
                null,
                $aRawIndexKeyList
              );
              break;
            case 'forum':
              $IntelId = \Grepodata\Library\IndexV2\ForumParser::ParseReport(
                $oUser->id,
                $World,
                $ReportRaw,
                $ReportHash,
                $ReportJson,
                $ReportInfo,
                $ReportPoster,
                $ReportPosterId,
                $ReportPosterAllyId,
                $ScriptVersion,
                $Locale,
                null,
                $aRawIndexKeyList
              );
              break;
            default:
              throw new \Exception("Unhandled report type: " . $ReportType);
          }
        } catch (InboxParserExceptionDebug | ForumParserExceptionDebug $e) {
          // Parsing failed due to an expected event, don't log as failed
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
          $IntelId = -1;
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionWarning | ForumParserExceptionWarning $e) {
          Logger::warning($LogPrefix . ' ('.$e->getMessage().')');
          $bParsingError = true;
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionError | ForumParserExceptionError $e) {
          Logger::error($LogPrefix . ' ('.$e->getMessage().')');
          $bParsingError = true;
          $Explain = $e->getMessage();
        } catch (\Exception $e) {
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().')');
          $bParsingError = true;
          $Explain = $e->getMessage();
        }

        if ($IntelId === null || $IntelId === false || $IntelId <= 0) {
          // Parsing failed, save record debug info
          $oIntel = new \Grepodata\Library\Model\IndexV2\Intel();
          $oIntel->indexed_by_user_id = $oUser->id;
          $oIntel->script_version = $ScriptVersion;
          $oIntel->poster_player_name = $ReportPoster;
          $oIntel->poster_player_id = $ReportPosterId;
          $oIntel->poster_alliance_id = $ReportPosterAllyId;
          $oIntel->source_type = $ReportType;
          $oIntel->report_type = IndexBuilderV2::generateIndexKey(32); // Random string to ignore unique index violation
          $oIntel->hash = $ReportHash;
          $oIntel->world = $World;
          $oIntel->report_json = $ReportJson;
          $oIntel->report_info = json_encode(substr($ReportInfo, 0, 100));
          $oIntel->parsing_failed = true; // Unable to read report
          $oIntel->parsing_error = $bParsingError;
          $oIntel->debug_explain = substr($Explain, 0, 1000);
          $oIntel->save();
          $IntelId = $oIntel->id;
          if (empty($IntelId)) {
            Logger::warning("Report parser failed to save debug report for hash $ReportHash");
            die(self::OutputJson(array(), 200));
          }
        }

        // Add the hash to all indexes for this user and add a hash record for self
        /** @var \Grepodata\Library\Model\Indexer\IndexInfo $oIndex */
        foreach ($aIndexes as $oIndex) {
          if ($oIndex->role != Roles::ROLE_READ && $oIndex->contribute == true) {
            // only save if user has write access on the index and if the user has chosen to contribute to this index
            IntelShared::saveHashToIndex($ReportHash, $IntelId, $oIndex, $ReportPosterId ?? null);

            // Toggle new report switch on index
            if ($oIndex->new_report != 1) {
              $oIndex->new_report = 1;
              $oIndex->save();
            }
          }
        }
        IntelShared::saveHashToUser($ReportHash, $IntelId, $oUser, $World, $ReportPosterId ?? null);
      }

      die(self::OutputJson(array(), 200));

    } catch (\Exception $e){
      Logger::error('Error processing indexer add inbox report: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

}
