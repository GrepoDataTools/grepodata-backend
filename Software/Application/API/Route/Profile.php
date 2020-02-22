<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ErrorCode;

class Profile extends BaseRoute
{
  public static function IndexesGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    if ($oUser->is_confirmed==false) {
      ErrorCode::code(3010, array(), 403);
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