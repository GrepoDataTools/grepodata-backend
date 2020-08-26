<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;

class Profile extends BaseRoute
{
  public static function IndexesGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    $aResponse = array();
    $aIndexes = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUser($oUser);
    foreach ($aIndexes as $oIndex) {
      $aOverview = [];
      if (isset($aParams['expand_overview'])) {
        try {
          $oOverview = IndexOverview::firstOrFail($oIndex->key_code);
          $aOverview = $oOverview->getPublicFields();
        } catch (\Exception $e) {
          continue;
        }
      }
      $aResponse[] = array(
        'key' => $oIndex->key_code,
        'name' => $oIndex->index_name,
        'role' => $oIndex->role,
        'contribute' => $oIndex->contribute,
        'world' => $oIndex->world,
        'created_at' => $oIndex->created_at,
        'updated_at' => $oIndex->updated_at,
        'overview' => $aOverview
      );
    }

    return self::OutputJson($aResponse);
  }

}