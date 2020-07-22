<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\Indexer\Report;
use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\ReportId;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Discord extends \Grepodata\Library\Router\BaseRoute
{
  /**
   * @return string
   * @throws \Exception
   */
  public static function SetServerGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild', 'server'));

      // Check if server exists
      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['server']);
      if ($oWorld->stopped === 1) {
        throw new ModelNotFoundException("World is stopped");
      }

      // Save settings
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);
      $oDiscord->server = $oWorld->grep_id;
      $oDiscord->save();

      $aResponse = array('success' => true);
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Error setting server for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * @return string
   * @throws \Exception
   */
  public static function SetIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild', 'index'));

      // Check if index exists
      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($aParams['index']);
      if ($oIndex->moved_to_index !== null) {
        throw new \Exception("Index is moved");
      }

      // Save settings
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);
      $oDiscord->index_key = $oIndex->key_code;
      $oDiscord->save();

      $aResponse = array('success' => true);
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Error setting index for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * @return string
   * @throws \Exception
   */
  public static function GetIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild'));

      // Get settings
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrFail($aParams['guild']);

      if ($oDiscord->index_key === null) {
        throw new ModelNotFoundException();
      }

      $aResponse = array('key' => $oDiscord->index_key, 'userscript' => md5($oDiscord->index_key));
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Error getting index for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * @return string
   * @throws \Exception
   */
  public static function GetSettingsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild'));

      // Get settings
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);

      $aResponse = $oDiscord->getPublicFields();

      if (!is_null($oDiscord->index_key)) {
        $aResponse['userscript'] = md5($oDiscord->index_key);
      } else {
        $aResponse['userscript'] = null;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Error getting guild settings for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * @return string
   * @throws \Exception
   */
  public static function GetReportByHashGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild', 'hash'));
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);

      // Find hash
      try {
        if (strpos($aParams['hash'], 'r') === 0) {
          // Use new hash version without required index key
          $SearchHash = str_replace('r', '', $aParams['hash']);
          $SearchHash = str_replace('m', '-', $SearchHash);
          /** @var ReportId $oReportHash */
          $oReportHash = \Grepodata\Library\Model\Indexer\ReportId::where('report_id', '=', $SearchHash)
            ->where('index_report_id', '>', 0)
            ->orderBy('id', 'desc')
            ->firstOrFail();
        } else {
          // Old hash version: get required settings
          if ($oDiscord->index_key === null) {
            ResponseCode::errorCode(5001); // index key required
          }
          $oReportHash = \Grepodata\Library\Controller\Indexer\ReportId::firstByIndexByHash($oDiscord->index_key, $aParams['hash']);
        }
      } catch (ModelNotFoundException $e) {
        // If this step fails, report has not been indexed yet
        ResponseCode::errorCode(5000);
      }
      $oIndex = IndexInfo::firstOrFail($oReportHash->index_key);

      try {
        $oReport = Report::firstById($oReportHash->index_report_id);
      } catch (ModelNotFoundException $e) {
        // If this step fails, report has been indexed but we do not have the information about the report
        ResponseCode::custom("Report has been indexed but we are unable to rebuild report for this hash (report info not found)", 5000);
      }

      if ($oReport->report_json == null || $oReport->report_json == '') {
        // if $oReport->report_json is empty, report html is not available and probably never will be
        ResponseCode::custom("Report has been indexed but we are unable to rebuild report for this hash (lost html)", 5000);
      }

      try {
        $html = \Grepodata\Library\Indexer\Helper::JsonToHtml($oReport, true);
        $Url = Helper::reportToImage($html, $oReport, $aParams['hash']);
      } catch (\Exception $e) {
        ResponseCode::errorCode(5004);
      }

      $aResponse = array(
        'success' => true,
        'url' => $Url,
        'index' => null,
        'world' => $oIndex->world,
        'bb' => array()
      );

      // Try to expand with info
      try {
        if ($oDiscord->index_key === $oReport->index_code) {
          $aResponse['index'] = $oIndex->key_code;
          if (isset($oReport->city_id) && $oReport->city_id > 0) {
            $oCity = CityInfo::getById($oReport->index_code, $oReport->city_id);
            $aResponse['intel'] = $oCity->getPublicFields();
          }
        } else {
          // Show BBcode only
          preg_match_all('/#[a-zA-Z0-9]{18,1000}={0,2}/m', $html, $matches, PREG_SET_ORDER, 0);
          foreach ($matches as $match) {
            $aData = json_decode(base64_decode($match[0]), true);
            if (is_array($aData) && sizeof($aData)>0) {
              $aResponse['bb'][] = $aData;
            }
          }
        }
      } catch (\Exception $e) {}

      return self::OutputJson($aResponse);

    } catch (\Exception $e) {
      die(self::OutputJson(array(
        'success'     => false,
        'message'     => 'Error creating image for these parameters: ' . $e->getMessage(),
        'parameters'  => $aParams
      ), 500));
    }
  }
}