<?php

namespace Grepodata\Application\API\Route\Indexer;

use Grepodata\Library\Controller\Indexer\ReportId;
use Grepodata\Library\Exception\ForumParserExceptionDebug;
use Grepodata\Library\Exception\ForumParserExceptionError;
use Grepodata\Library\Exception\ForumParserExceptionWarning;
use Grepodata\Library\Exception\InboxParserExceptionDebug;
use Grepodata\Library\Exception\InboxParserExceptionError;
use Grepodata\Library\Exception\InboxParserExceptionWarning;
use Grepodata\Library\Indexer\ForumParser;
use Grepodata\Library\Indexer\InboxParser;
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
        Logger::debugInfo("filter => key: ".$aParams['key'].", id: ".$aParams['player_id'].", value: ".$aParams['filter']);
      }

      die(self::OutputJson($aResponse, 200));
    } catch (\Exception $e){
      Logger::error('Error retrieving latest indexer hashes: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

  public static function AddReportFromInboxPOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'report_hash', 'report_text', 'report_json', 'report_poster', 'report_poster_id', 'report_poster_ally_id', 'script_version'));

      if (!is_array($aParams['key'])) {
        $aParams['key'] = array($aParams['key']);
      }

      foreach ($aParams['key'] as $Key) {
        // Get data
        $ReportInfo = preg_replace('/\s+/', ' ', $aParams['report_text']);
        $ReportInfo = substr($ReportInfo, 0, 500);
        $ReportPoster = $aParams['report_poster'];
        $ReportPosterAllyId = $aParams['report_poster_ally_id'];
        $ScriptVersion = $aParams['script_version'];
        $ReportRaw = $aParams['report_json'];
        $ReportJson = json_encode($ReportRaw);

        // check if report already exists
        $ReportHash = $aParams['report_hash'];
        $ReportPosterId = $aParams['report_poster_id'];

        $ReportId = ReportId::getByHashIndex($ReportHash, $Key);
        if ($ReportId !== null) {
          // Update HTML for existing report
          try {
            $Report = \Grepodata\Library\Controller\Indexer\Report::firstById($ReportId->index_report_id);
            $bSave = false;
            if ($Report->report_json == '' || $Report->report_json == null) {
              $Report->report_json = $ReportJson;
              $bSave = true;
            }
            if ($Report->report_info == '' || $Report->report_info == null) {
              $Report->report_info = json_encode(substr($ReportInfo, 0, 100));
              $bSave = true;
            }
            if ($bSave == true) {
              $Report->save();
            }
          } catch (\Exception $e) {
            Logger::warning("Error updating html for report hash: $ReportHash and key: $Key");
          }
          continue;
        }

        // Validate index
        $bValidIndex = false;
        $oIndex = null;
        $Attempts = 0;
        while (!$bValidIndex && $Attempts <= 50) {
          $Attempts += 1;
          $oIndex = Validator::IsValidIndex($Key);
          if ($oIndex === null || $oIndex === false) {
            die(self::OutputJson(array(), 200));
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            $Key = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }

        // Add report
        $City_id = 0;
        $LogPrefix = 'Unable to parse inbox report with id ' . $ReportHash . ' [index '.$oIndex->key_code.' - v'.$ScriptVersion.' - world '.$oIndex->world.']';
        $Explain = null;
        try {
          $City_id = InboxParser::ParseReport($Key, $ReportRaw, $ReportPoster, $ReportPosterId, $ReportPosterAllyId, $ReportHash, substr($oIndex->world, 0, 2));
        } catch (InboxParserExceptionDebug $e) {
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
          $City_id = -1;
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionWarning $e) {
          Logger::warning($LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionError $e) {
          Logger::error($LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        } catch (\Exception $e) {
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().')');
          $Explain = $e->getMessage();
        }

        if ($City_id === null || $City_id === false) {
          $City_id = 0;
        }

        $oReport = new \Grepodata\Library\Model\Indexer\Report();
        $oReport->index_code      = $Key;
        $oReport->type            = 'inbox';
        $oReport->report_poster   = $ReportPoster;
        $oReport->fingerprint     = $ReportHash;
        $oReport->report_json     = $ReportJson;
        $oReport->report_info     = json_encode(substr($ReportInfo, 0, 100));
        $oReport->script_version  = $ScriptVersion;
        if (isset($City_id)) {
          $oReport->city_id = $City_id;
        }
        if ($Explain !== null) {
          $oReport->debug_explain = $Explain;
        }
        $oReport->save();

        $oReportId = new \Grepodata\Library\Model\Indexer\ReportId();
        $oReportId->report_id = $ReportHash;
        $oReportId->player_id = $ReportPosterId;
        $oReportId->index_key = $Key;
        $oReportId->index_report_id = (isset($oReport->id)?$oReport->id:0);
        $oReportId->index_type = 'inbox';
        $oReportId->save();

        if ($oIndex->new_report != 1) {
          $oIndex->new_report = 1;
          $oIndex->save();
        }
      }

      die(self::OutputJson(array(), 200));

    } catch (\Exception $e){
      Logger::error('Error processing indexer add inbox report: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

  public static function AddReportFromForumPOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('report_hash', 'key', 'report_text', 'report_json', 'report_poster', 'script_version'));

      if (!is_array($aParams['key'])) {
        $aParams['key'] = array($aParams['key']);
      }

      foreach ($aParams['key'] as $Key) {
        // Validate index
        $bValidIndex = false;
        $oIndex = null;
        $Attempts = 0;
        while (!$bValidIndex && $Attempts <= 50) {
          $Attempts += 1;
          $oIndex = Validator::IsValidIndex($Key);
          if ($oIndex === null || $oIndex === false) {
            die(self::OutputJson(array(), 200));
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            $Key = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }

        $ReportPoster = $aParams['report_poster'];
        $ScriptVersion = $aParams['script_version'];
        $ReportRaw = $aParams['report_json'];
        $ReportJson = json_encode($ReportRaw);

        $ReportInfo = preg_replace('/\s+/', ' ', $aParams['report_text']);
        $ReportInfo = substr($ReportInfo, 0, 500);

        // Check if hash exists
        $Hash = $aParams['report_hash'];
        $ReportId = ReportId::getByHashIndex($Hash, $Key);
        if ($ReportId !== null) {
          // Update HTML for existing report
          try {
            $Report = \Grepodata\Library\Controller\Indexer\Report::firstById($ReportId->index_report_id);
            $bSave = false;
            if ($Report->report_json==''||$Report->report_json==null) {
              $Report->report_json = $ReportJson;
              $bSave = true;
            }
            if ($Report->report_info==''||$Report->report_info==null) {
              $Report->report_info = json_encode(substr($ReportInfo, 0, 100));
              $bSave = true;
            }
            if ($bSave == true) {
              $Report->save();
            }
          } catch (\Exception $e) {
            Logger::warning("Error updating html for existing report hash: $Hash and key: $Key");
          }
          continue;
        }

        // Save hash to db
        $oReportId = new \Grepodata\Library\Model\Indexer\ReportId();
        $oReportId->report_id = $Hash;
        if (isset($_POST['report_poster_id']) && is_numeric($_POST['report_poster_id'])) {
          $oReportId->player_id = (int) $_POST['report_poster_id'];
        } else {
          $oReportId->player_id = 0;
        }
        $oReportId->index_key = $Key;
        $oReportId->index_report_id = 0;
        $oReportId->index_type = 'forum';

        // Add report
        $Explain = null;
        $lang = substr($oIndex->world, 0, 2);
        $City_id = 0;
        $LogPrefix = 'Unable to parse forum report with hash ' . $Hash . ' [index '.$oIndex->key_code.' - v'.$ScriptVersion.' - '.$lang.']';
        try {
          $aParseOutput = ForumParser::ParseReport($Key, $ReportRaw, $ReportPoster, $Hash, $lang);
        } catch (ForumParserExceptionDebug $e) {
          $City_id = -1;
          $Explain = $e->getMessage();
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
        } catch (ForumParserExceptionWarning $e) {
          $Explain = $e->getMessage();
          Logger::warning($LogPrefix . ' ('.$e->getMessage().')');
        } catch (ForumParserExceptionError $e) {
          $Explain = $e->getMessage();
          Logger::error($LogPrefix . ' ('.$e->getMessage().')');
        } catch (\Exception $e) {
          $Explain = $e->getMessage();
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().')');
        }

        if ($City_id !== -1) {
          if (!isset($aParseOutput) || $aParseOutput === null || $aParseOutput === false || !is_array($aParseOutput)) {
            $City_id = 0;
          } else if (isset($aParseOutput['id'])) {
            $City_id = $aParseOutput['id'];
          }
        }
        
        $oReport = new \Grepodata\Library\Model\Indexer\Report();
        $oReport->index_code      = $Key;
        $oReport->type            = 'forum';
        $oReport->report_poster   = $ReportPoster;
        $oReport->fingerprint     = $Hash;
        $oReport->report_json     = $ReportJson;
        $oReport->report_info     = json_encode(substr($ReportInfo, 0, 100));
        $oReport->script_version  = $ScriptVersion;
        if (isset($City_id)) {
          $oReport->city_id = $City_id;
        }
        if ($Explain !== null) {
          $oReport->debug_explain = $Explain;
        }
        $oReport->save();

        if (isset($oReportId)) {
          $oReportId->index_report_id = (isset($oReport->id) ? $oReport->id : 0);
          $oReportId->index_type = 'forum';
          $oReportId->save();
        }

        if ($oIndex->new_report != 1) {
          $oIndex->new_report = 1;
          $oIndex->save();
        }
      }

      die(self::OutputJson(array(), 200));

    } catch (\Exception $e) {
      Logger::warning('Error processing indexer add report: ' . $e->getMessage());
      die(self::OutputJson(array(), 200));
    }
  }

}