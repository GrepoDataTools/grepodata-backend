<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Router\BaseRoute;

class Profile extends BaseRoute
{
  public static function IndexesGET()
  {
    //TODO: TEMP
    if (!bDevelopmentMode) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // TODO: TEMP
    if (!in_array($oUser->email, ['admin@grepodata.com'])) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

    $aResponse = array();
    $aIndexesRaw = \Grepodata\Library\Controller\Indexer\IndexInfo::allByMail($oUser->email);
    foreach ($aIndexesRaw as $oIndex) {
      if ($oIndex->moved_to_index !== null) {
        continue;
      }
      $oOverview = [];
      try {
        $oOverview = IndexOverview::firstOrFail($oIndex->key_code);
      } catch (\Exception $e) {}
      $aResponse[] = array(
        'key' => $oIndex->key_code,
        'world' => $oIndex->world,
        'created_at' => $oIndex->created_at,
        'updated_at' => $oIndex->updated_at,
        'overview' => $oOverview->getPublicFields()
      );
    }

    return self::OutputJson($aResponse);
  }

}