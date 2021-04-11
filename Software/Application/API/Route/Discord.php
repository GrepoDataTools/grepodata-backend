<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Model\Indexer\ReportId;
use Grepodata\Library\Model\IndexV2\Intel;
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
    // TODO: add discord account linking for additional security
    // 1. if a user wants to share a report, they must link their discord account to their grepodata account
    // 2. sharing is then done using the discord user id and a report hash
    // 3. grepodata user is derived from discord user id
    // 4. list of indexes is derived from grepodata user
    // 5. if the report hash occurs in the authenticated index list, only then we show the report
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('guild', 'hash'));
//      $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);

      // Find hash
      try {
        if (strpos($aParams['hash'], 'r') === 0) {
          // Use new hash version without required index key
          $SearchHash = str_replace('r', '', $aParams['hash']);
          $SearchHash = str_replace('m', '-', $SearchHash);
          /** @var ReportId $oReportHash */
          $oIntel = Intel::where('hash', '=', $SearchHash)
            ->orderBy('id', 'desc')
            ->firstOrFail();
        } else {
          ResponseCode::errorCode(5000);
        }
      } catch (ModelNotFoundException $e) {
        // If this step fails, report has not been indexed yet
        ResponseCode::errorCode(5000);
      }

      if ($oIntel->report_json == null || $oIntel->report_json == '') {
        // if $oReport->report_json is empty, report html is not available and probably never will be
        ResponseCode::custom("Report has been indexed but we are unable to rebuild report for this hash (lost html)", 5000);
      }

      try {
        $html = \Grepodata\Library\Indexer\Helper::JsonToHtml($oIntel, true);
        $Url = Helper::reportToImage($html, $oIntel, $aParams['hash']);
      } catch (\Exception $e) {
        ResponseCode::errorCode(5004);
      }

      $aResponse = array(
        'success' => true,
        'url' => $Url,
        'b64' => '',
        //'b64' => base64_encode(file_get_contents($Url)),
        'index' => null,
        'world' => $oIntel->world,
        'town_id' => $oIntel->town_id,
        'town_name' => $oIntel->town_name,
        'player_id' => $oIntel->player_id,
        'player_name' => $oIntel->player_name,
        'bb' => array()
      );

//      // Try to expand with info
//      try {
//        // Show BBcode
//        preg_match_all('/#[a-zA-Z0-9]{18,1000}={0,2}/m', $html, $matches, PREG_SET_ORDER, 0);
//        foreach ($matches as $match) {
//          $aData = json_decode(base64_decode($match[0]), true);
//          if (is_array($aData) && sizeof($aData)>0) {
//            $aResponse['bb'][] = $aData;
//          }
//        }
//      } catch (\Exception $e) {}

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
