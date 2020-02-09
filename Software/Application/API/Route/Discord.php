<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\Report;
use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\ReportId;
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

      // Get settings
      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrFail($aParams['guild']);

      if ($oDiscord->index_key === null) {
        // TODO error codes
        throw new \Exception("This guild has not set a default index");
      }

      try {
        $oReportHash = \Grepodata\Library\Controller\Indexer\ReportId::firstByIndexByHash($oDiscord->index_key, $aParams['hash']);
      } catch (ModelNotFoundException $e) {
        // If this step fails, report has not been indexed yet
        throw new \Exception("No report found for hash ". $aParams['hash'] . " in index " . $oDiscord->index_key);
      }

      try {
        $oReport = Report::firstById($oReportHash->index_report_id);
      } catch (ModelNotFoundException $e) {
        // If this step fails, report has been indexed but we do not have the information about the report
        throw new \Exception("Report has been indexed but we are unable to rebuild report for this hash (report info not found)");
      }

      if ($oReport->report_json == null || $oReport->report_json == '') {
        // if $oReport->report_json is empty, report html is not available and probably never will be
        throw new \Exception("Report has been indexed but we are unable to rebuild report for this hash (lost html)");
      }

      $Url = Helper::reportToImage($oReport, $aParams['hash']);

      $aResponse = array(
        'success' => true,
        'url' => $Url
      );
      return self::OutputJson($aResponse);

    } catch (\Exception $e) {
      die(self::OutputJson(array(
        'success'     => false,
        'message'     => 'Error creating image for these parameters: ' . $e->getMessage(),
        'parameters'  => $aParams
      ), 404));
    }
  }
}