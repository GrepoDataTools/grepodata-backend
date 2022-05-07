<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Event;
use Grepodata\Library\Controller\IndexV2\OwnersActual;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexOwners extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns a list of all index owners for the given index
   */
  public static function IndexOwnersGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      $aOwners = OwnersActual::getAllByIndex($oIndex);

//      $aResult = IndexOverview::firstOrFail($aParams['index_key']);
//      $aItems = json_decode($aResult->owners);

      $aOwnersResponse = array();
      foreach ($aOwners as $oOwner) {
        $aOwnersResponse[] = $oOwner->getPublicFields();
      }

      $aResponse = array(
        'size'    => sizeof($aOwners),
        'data'   => $aOwnersResponse
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Index owner not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Change the intel visibility status of an owner
   * @throws \Exception
   */
  public static function IndexOwnersPUT()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'alliance_id', 'is_hidden'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2020);
      }

      if (!in_array($aParams['is_hidden'], array('true', 'false', true, false))) {
        ResponseCode::errorCode(7531);
      }
      $bIsHidden = $aParams['is_hidden'] === true || $aParams['is_hidden'] === 'true';

      // Get owner
      try {
        $oOwner = OwnersActual::getByIndexByAllianceId($oIndex, $aParams['alliance_id']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2030);
      }

      // Update
      $oOwner->hide_intel = $bIsHidden;
      $oOwner->save();

      ResponseCode::success(array(
        'size' => 1,
        'data' => $oOwner->getPublicFields()
      ));

    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(7560);
    }
  }

  /**
   * Add a new alliance to the list of index owners
   * @throws \Exception
   */
  public static function IndexOwnersPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'alliance_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2020);
      }

      try {
        $oAlliance = Alliance::firstOrFail($aParams['alliance_id'], $oIndex->world);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2040);
      }

      // Check if owner already exists
      try {
        $oOwner = OwnersActual::getByIndexByAllianceId($oIndex, $aParams['alliance_id']);
        if (!empty($oOwner)) {
          // Owner already exists, don't continue
          ResponseCode::errorCode(7533);
        }
      } catch (ModelNotFoundException $e) {
        // owner does not yet exist, continue
      }

      // Create new owner
      try {
        $oOwnerActual = new \Grepodata\Library\Model\IndexV2\OwnersActual();
        $oOwnerActual->index_key = $oIndex->key_code;
        $oOwnerActual->alliance_id = $oAlliance->grep_id;
        $oOwnerActual->alliance_name = $oAlliance->name;
        $oOwnerActual->hide_intel = false; // Default = false
        $oOwnerActual->share = 0;
        $oOwnerActual->save();

        Event::addOwnerAllianceEvent($oIndex, $oAlliance->name, 'manual_add', $oUser);
      } catch (\Exception $e) {
        // duplicate entry, continue
        $t=2;
      }

      // Update inclusion list
      try {
        $oIndexOwners = \Grepodata\Library\Controller\IndexV2\IndexOwners::firstOrNew($oIndex->key_code);
        $oIndexOwners->key_code = $oIndex->key_code;
        $oIndexOwners->world = $oIndex->world;
        $aIncludes = json_decode($oIndexOwners->getOwnersIncluded(), true);
        $aIncludes[] = array(
          'alliance_id' => $oAlliance->grep_id,
          'alliance_name' => $oAlliance->name
        );
        $oIndexOwners->setOwnersIncluded($aIncludes);

        // Remove alliance from excluded if present
        $aExcluded = json_decode($oIndexOwners->getOwnersExcluded(), true);
        if ($aExcluded != null && is_array($aExcluded)) {
          $aExcludedUpdate = array();
          foreach ($aExcluded as $aExclude) {
            if (!in_array($aExclude['alliance_id'], array_column($aIncludes, 'alliance_id'))) {
              $aExcludedUpdate[] = $aExclude;
            }
          }
          $oIndexOwners->setOwnersExcluded($aExcludedUpdate);
        }

        // Save inclusion list
        $oIndexOwners->save();
      } catch (\Exception $e) {
        Logger::error("Error updating (add) owner inclusions list for index " . $oIndex->key_code . ": " . $e->getMessage());
      }

      // Rebuild overview
      \Grepodata\Library\Controller\IndexV2\IndexOverview::buildIndexOverview($oIndex);

      ResponseCode::success(array(
        'size' => 1,
        'data' => $oOwnerActual->getPublicFields()
      ));

    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(7560);
    }
  }

  /**
   * Remove an alliance from the list of index owners
   * @throws \Exception
   */
  public static function IndexOwnersDELETE()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'alliance_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2020);
      }

      // Delete owner actual record
      $AllianceName = '';
      try {
        $oOwner = OwnersActual::getByIndexByAllianceId($oIndex, $aParams['alliance_id']);
        $AllianceName = $oOwner->alliance_name;
        $oOwner->delete();
      } catch (ModelNotFoundException $e) {}

      // Update inclusion list
      try {
        $oIndexOwners = \Grepodata\Library\Controller\IndexV2\IndexOwners::firstOrNew($oIndex->key_code);
        $oIndexOwners->key_code = $oIndex->key_code;
        $oIndexOwners->world = $oIndex->world;
        $aExcludes = (array) json_decode($oIndexOwners->getOwnersExcluded(), true);

        try {
          // If alliance still exists, add to exclusion list
          $oAlliance = Alliance::firstOrFail($aParams['alliance_id'], $oIndex->world);
          $aExcludes[] = array(
            'alliance_id' => $oAlliance->grep_id,
            'alliance_name' => $oAlliance->name
          );
          $oIndexOwners->setOwnersExcluded($aExcludes);
        } catch (\Exception $e) {}

        // remove from included if present
        $aIncluded = (array) json_decode($oIndexOwners->getOwnersIncluded(), true);
        if ($aIncluded != null && is_array($aIncluded)) {
          $aIncludedUpdate = array();
          foreach ($aIncluded as $aInclude) {
            if (!in_array($aInclude['alliance_id'], array_column($aExcludes, 'alliance_id'))) {
              $aIncludedUpdate[] = $aInclude;
            }
          }
          $oIndexOwners->setOwnersIncluded($aIncludedUpdate);
        }

        $oIndexOwners->save();
      } catch (\Exception $e) {
        Logger::error("Error updating (remove) owner inclusions list for index " . $oIndex->key_code . ": " . $e->getMessage());
      }

      // Rebuild overview
      \Grepodata\Library\Controller\IndexV2\IndexOverview::buildIndexOverview($oIndex);

      if ($AllianceName != '') {
        Event::addOwnerAllianceEvent($oIndex, $AllianceName, 'manual_remove', $oUser);
      }

      ResponseCode::success(array(
        'size' => 1,
        'removed_id' => (int) $aParams['alliance_id']
      ));

    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(7560);
    }
  }

}
