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
use Illuminate\Support\Facades\Log;

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
      $aParams = self::validateParams(array('key', 'report_hash', 'report_text', 'report_json', 'type', 'report_poster', 'report_poster_id', 'report_poster_ally_id', 'script_version'));

      if (!is_array($aParams['key'])) {
        $aParams['key'] = array($aParams['key']);
      }

//      if (sizeof($aParams['key'])>1) {
//        Logger::debugInfo("multi key request: " . json_encode($aParams['key']));
//      }

      foreach ($aParams['key'] as $Key) {
        // Get data
        $ReportInfo = preg_replace('/\s+/', ' ', $aParams['report_text']);
        $ReportInfo = substr($ReportInfo, 0, 500);
        $Fingerprint = md5($ReportInfo);
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
            if ($Report->report_json == '' || $Report->report_json == null) {
              $Report->report_json = $ReportJson;
            }
            if ($Report->report_info == '' || $Report->report_info == null) {
              $Report->report_info = $ReportInfo;
            }
            $Report->save();
          } catch (\Exception $e) {
            Logger::warning("Error updating html for report hash: $ReportHash and key: $Key");
          }
          continue;
        }
        $ReportId = $ReportHash;

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
        $LogPrefix = 'Unable to parse inbox report with id ' . $ReportHash . ' [index '.$oIndex->key_code.' - poster '.$ReportPosterId.' - v'.$ScriptVersion.']';
        $Debug = false;
        $Explain = null;
        try {
          $City_id = InboxParser::ParseReport($Key, $ReportRaw, $ReportPoster, $ReportPosterId, $ReportPosterAllyId, $Fingerprint, substr($oIndex->world, 0, 2));
        } catch (InboxParserExceptionDebug $e) {
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
          $City_id = -1;
          $Explain = $e->getMessage();
          $Debug = true;
        } catch (InboxParserExceptionWarning $e) {
          Logger::warning($LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 100).'}');
          $Explain = $e->getMessage();
        } catch (InboxParserExceptionError $e) {
          Logger::error($LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 250).'}');
          $Explain = $e->getMessage();
        } catch (\Exception $e) {
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 250).'}');
          $Explain = $e->getMessage();
        }

        if ($City_id === null || $City_id === false) {
          $City_id = 0;
          $Debug = true;
        }

        $oReport = new \Grepodata\Library\Model\Indexer\Report();
        $oReport->index_code      = $Key;
        $oReport->type            = $aParams['type'];
        $oReport->report_poster   = $ReportPoster;
        $oReport->fingerprint     = $Fingerprint;
        $oReport->report_json     = $ReportJson;
        $oReport->report_info     = substr($ReportInfo, 0, 100);
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
      $aParams = self::validateParams(array('key', 'report_text', 'report_json', 'type', 'report_poster', 'script_version'));

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

        // check duplicates
        $bCheckFingerprint = false;
        if (isset($_POST['report_hash'])) {
          $Hash = $_POST['report_hash'];
          $ReportId = ReportId::getByHashIndex($Hash, $Key);
          if ($ReportId !== null) {
            // Update HTML for existing report
            try {
              $Report = \Grepodata\Library\Controller\Indexer\Report::firstById($ReportId->index_report_id);
              if ($Report->report_json==''||$Report->report_json==null) {
                $Report->report_json = $ReportJson;
              }
              if ($Report->report_info==''||$Report->report_info==null) {
                $Report->report_info = $ReportInfo;
              }
              $Report->save();
            } catch (\Exception $e) {
              Logger::warning("Error updating html for report hash: $Hash and key: $Key");
            }
            continue;
          } else {
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
          }
        } else {
          $bCheckFingerprint = true;
        }

        $Debug = false;
        $Explain = null;

        // Check for incompatible reports
        $aExclusionStrings = array();
        $lang = 'nl';
        if (strpos($oIndex->world, 'nl') !== false) {
          $lang = 'nl';
          $aExclusionStrings = array(
            'valt je ondersteunende troepen in',
            'verovert',
            'wijsheid',
            'Wijsheid'
          );
        } else if (strpos($oIndex->world, 'en') !== false) {
          $lang = 'en';
          $aExclusionStrings = array(
            'is attacking your support in',
            'is conquering',
            'wisdom',
            'Wisdom',
          );
        } else if (strpos($oIndex->world, 'fr') !== false) {
          $lang = 'fr';
          $aExclusionStrings = array(
            'attaque ton soutien en',
            'attrape ton soutien',
            'ton soutien',
            'conquête',
            'conquiert',
            'conquis',
          );
        } else if (strpos($oIndex->world, 'de') !== false) {
          $lang = 'de';
          $aExclusionStrings = array(
            'greift deine Unterstützung in',
            'erobert',
            'weisheit',
            'Weisheit'
          );
        } else {
          $lang = substr($oIndex->world, 0, 2);
          $aExclusionStrings = array();
          //$Debug = true;
          //$Explain = "CHECK NEW SERVER SETTINGS: " . $lang;
        }

        $bExclude = false;
        foreach ($aExclusionStrings as $Exclusion) {
          if (strpos($aParams['report_text'], $Exclusion) !== false) {
            $bExclude = true;
          }
        }

        if ($bExclude===true) {
          if (isset($oReportId)) {
            $oReportId->save();
            Logger::debugInfo("[index ".$Key."] Skipping report [report id ".$oReportId->id."] with exclusion match: " . $aParams['report_text']);
          } else {
            Logger::debugInfo("[index ".$Key."] Skipping report with exclusion match: " . $aParams['report_text']);
          }
          continue;
        }

        // Check if report already exists
        $Fingerprint = md5($ReportInfo);
        if (\Grepodata\Library\Controller\Indexer\Report::exists($Fingerprint, $Key)) {
          if (isset($oReportId)) {
            $oReportId->save();
          }
          continue;
        }

        // Add report
        $City_id = 0;
        $LogPrefix = 'Unable to parse forum report with fingerprint ' . $Fingerprint . ' [index '.$oIndex->key_code.' - v'.$ScriptVersion.' - '.$lang.']';
        try {
          $aParseOutput = ForumParser::ParseReport($Key, $ReportRaw, $ReportPoster, $Fingerprint, $lang);
        } catch (ForumParserExceptionDebug $e) {
          $City_id = -1;
          $Debug = true;
          $Explain = $e->getMessage();
          Logger::debugInfo($LogPrefix . ' ('.$e->getMessage().')');
        } catch (ForumParserExceptionWarning $e) {
          $Explain = $e->getMessage();
          Logger::warning($LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 100).'}');
        } catch (ForumParserExceptionError $e) {
          $Explain = $e->getMessage();
          Logger::error($LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 250).'}');
        } catch (\Exception $e) {
          $Explain = $e->getMessage();
          Logger::error('UNEXPECTED: ' . $LogPrefix . ' ('.$e->getMessage().') {'. substr($ReportInfo, 0, 250).'}');
        }

        if ($City_id !== -1) {
          if (!isset($aParseOutput) || $aParseOutput === null || $aParseOutput === false || !is_array($aParseOutput)) {
            $City_id = 0;
            $Debug = true;
          } else if (isset($aParseOutput['id'])) {
            $City_id = $aParseOutput['id'];
            $Debug = $aParseOutput['debug'];
          }
        }
        
        $oReport = new \Grepodata\Library\Model\Indexer\Report();
        $oReport->index_code      = $Key;
        $oReport->type            = $aParams['type'];
        $oReport->report_poster   = $ReportPoster;
        $oReport->fingerprint     = $Fingerprint;
        $oReport->report_json     = $ReportJson;
        $oReport->report_info     = $ReportInfo;
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
//      die(self::OutputJson(array(
//        'message'     => 'Something went wrong while indexing your report. please contact us if this error persists.'
//      ), 500));
      die(self::OutputJson(array(), 200));
    }
  }

}