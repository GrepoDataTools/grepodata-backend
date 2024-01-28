<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Exception;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Router\ResponseCode;

class Search extends \Grepodata\Library\Router\BaseRoute
{

  public static function SearchUsersGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'query'));
      $oSearchingUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      if (strlen($aParams['query'])<4) {
        ResponseCode::errorCode(6400);
      }

      // Find users
      $aUsers = User::SearchUser($aParams['query'], 0, 15);

      // Results
      $aSearchResults = array();
      foreach ($aUsers as $oUser) {
        $aSearchResults[] = array(
          'uid' => $oUser->id,
          'username' => $oUser->username
        );
      }

      $aResponse = array(
        'size' => sizeof($aSearchResults),
        'data' => $aSearchResults
      );

      ResponseCode::success($aResponse);
    } catch (Exception $e) {
      ResponseCode::errorCode(6401);
    }
  }

}
