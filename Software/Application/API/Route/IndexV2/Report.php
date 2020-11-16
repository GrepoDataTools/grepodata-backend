<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\Indexer\ReportId;
use Grepodata\Library\Controller\IndexV2\Intel;
use Grepodata\Library\Controller\IndexV2\IntelShared;
use Grepodata\Library\Exception\DuplicateIntelWarning;
use Grepodata\Library\Exception\ForumParserExceptionDebug;
use Grepodata\Library\Exception\ForumParserExceptionError;
use Grepodata\Library\Exception\ForumParserExceptionWarning;
use Grepodata\Library\Exception\InboxParserExceptionDebug;
use Grepodata\Library\Exception\InboxParserExceptionError;
use Grepodata\Library\Exception\InboxParserExceptionWarning;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;

class Report extends \Grepodata\Library\Router\BaseRoute
{
  public static function LatestReportHashesGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'player_id'));

      // Validate index
      $bValidIndex = false;
      $oIndex = null;
      $Attempts = 0;
      while (!$bValidIndex && $Attempts <= 50) {
        $Attempts += 1;
        $oIndex = Validator::IsValidIndex($aParams['key']);
        if ($oIndex === null || $oIndex === false) {
          die(self::OutputJson(array(), 200));
        }
        if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
          $aParams['key'] = $oIndex->moved_to_index; // redirect to new index
        } else {
          $bValidIndex = true;
        }
      }

      $aResponse = array('i'=>array(),'f'=>array());
      // inbox history
      $aHashes = ReportId::latestByIndexKeyByPlayer($oIndex->key_code, $aParams['player_id'], 50);
      /** @var \Grepodata\Library\Model\Indexer\ReportId $Id */
      foreach ($aHashes as $Id) {
        $aResponse['i'][] = $Id->report_id;
      }
      // forum history
      $aHashes = ReportId::latestByIndexKey($oIndex->key_code, 300);
      /** @var \Grepodata\Library\Model\Indexer\ReportId $Id */
      foreach ($aHashes as $Id) {
        $aResponse['f'][] = $Id->report_id;
      }
      if (isset($aParams['filter'])) {
        // TODO: parse filter
        Logger::indexDebug("filter => key: ".$aParams['key'].", id: ".$aParams['player_id'].", value: ".$aParams['filter']);
      }

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
      $aParams = self::validateParams(array('report_type', 'access_token', 'world', 'report_hash', 'report_text', 'report_json', 'report_poster', 'report_poster_id', 'script_version'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Find linked player for this user and this world
//      $oLinkedPlayer = null;
//      try {
//        $oLinkedPlayer = Linked::getByPlayerIdAndServer($oUser, $aParams['report_poster_id'], substr($aParams['world'], 0, 2));
//        if (!$oLinkedPlayer->confirmed) {
//          $oLinkedPlayer = null;
//          throw new ModelNotFoundException();
//        }
//      } catch (ModelNotFoundException $e) {
//        // Try to find any other player object in this world, verified by this user
//        $aLinkedPlayers = Linked::getAllByUser($oUser);
//        /** @var \Grepodata\Library\Model\IndexV2\Linked $oLinkedPlayer */
//        foreach ($aLinkedPlayers as $oLinkedPlayerPotential) {
//          if ($oLinkedPlayerPotential->confirmed == true
//            && $oLinkedPlayerPotential->server == substr($aParams['world'], 0, 2)
//          ) {
//            $oPlayer = Player::firstById($aParams['world'], $oLinkedPlayerPotential->player_id);
//            if ($oPlayer !== false) {
//              $oLinkedPlayer = $oLinkedPlayerPotential;
//            }
//          }
//        }
//      }
//
//      if ($oLinkedPlayer == null) {
//        // Unable to find linked accounts, user should probably verify new account
//        ResponseCode::errorCode(7100, array(), 401);
//      }

      // Get data
      $ReportType = $aParams['report_type'];
      $ReportInfo = preg_replace('/\s+/', ' ', $aParams['report_text']);
      $ReportInfo = substr($ReportInfo, 0, 500);
      $ReportPoster = $aParams['report_poster'];
      $ScriptVersion = $aParams['script_version'];
      $ReportRaw = $aParams['report_json'];
      $ReportJson = json_encode($ReportRaw);
      $ReportHash = $aParams['report_hash'];
      $ReportPosterId = $aParams['report_poster_id'];
      $World = $aParams['world'];
      $Locale = substr($World, 0, 2);

      // Get indexes for player
      $aIndexes = IndexInfo::allByUserAndWorld($oUser, $World);

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
          IntelShared::saveHashToUser($ReportHash, $IntelId, $oUser, $World);
        }
        foreach ($aIndexes as $oIndex) {
          if (!in_array($oIndex->key_code, $aIndexKeys)) {
            IntelShared::saveHashToIndex($ReportHash, $IntelId, $oIndex);

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
        try {
          switch ($ReportType) {
            case 'inbox':
              $IntelId = \Grepodata\Library\IndexV2\InboxParser::ParseReport(
                $oUser->id,
                $World,
                $ReportRaw,
                $ReportPoster,
                $ReportPosterId,
                $ReportHash,
                $ReportJson,
                $ReportInfo,
                $ScriptVersion,
                $Locale
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
                $ScriptVersion,
                $Locale);
              break;
            default:
              throw new \Exception("Unhandled report type: " . $ReportType);
          }
        } catch (InboxParserExceptionDebug | ForumParserExceptionDebug $e) {
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
          $IntelId = -1;
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionWarning | ForumParserExceptionWarning $e) {
          Logger::warning($LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionError | ForumParserExceptionError $e) {
          Logger::error($LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        } catch (\Exception $e) {
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        }

        if ($IntelId === null || $IntelId === false || $IntelId <= 0) {
          // Parsing failed, save record debug info
          $oIntel = new \Grepodata\Library\Model\IndexV2\Intel();
          $oIntel->indexed_by_user_id = $oUser->id;
          $oIntel->script_version = $ScriptVersion;
          $oIntel->source_type = $ReportType;
          $oIntel->report_type = IndexBuilderV2::generateIndexKey(32); // Random string to ignore unique index violation
          $oIntel->hash = $ReportHash;
          $oIntel->world = $World;
          $oIntel->report_json = $ReportJson;
          $oIntel->report_info = json_encode(substr($ReportInfo, 0, 100));
          $oIntel->parsing_failed = true;
          $oIntel->debug_explain = $Explain;
          $IntelId = $oIntel->save();
        }

        // Add the hash to all indexes for this user and add a hash record for self
        /** @var \Grepodata\Library\Model\Indexer\IndexInfo $oIndex */
        foreach ($aIndexes as $oIndex) {
          IntelShared::saveHashToIndex($ReportHash, $IntelId, $oIndex);

          // Toggle new report switch on index
          if ($oIndex->new_report != 1) {
            $oIndex->new_report = 1;
            $oIndex->save();
          }
        }
        IntelShared::saveHashToUser($ReportHash, $IntelId, $oUser, $World);
      }

      die(self::OutputJson(array(), 200));

    } catch (\Exception $e){
      Logger::error('Error processing indexer add inbox report: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

}