<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Exception;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;

class Notes extends \Grepodata\Library\Router\BaseRoute
{

  public static function AddNotePOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'world', 'town_id', 'poster_name', 'poster_id', 'message'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Get world
      $oWorld = World::getWorldById($aParams['world']);

      // Get indexes for player
      $aIndexList = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUserAndWorld($oUser, $oWorld->grep_id);

      // Save note to each index
      $PrimaryId = 0;
      foreach ($aIndexList as $oIndex) {
        $oNote = new \Grepodata\Library\Model\IndexV2\Notes();
        $oNote->index_key = $oIndex->key_code;
        $oNote->world = $oWorld->grep_id;
        $oNote->user_id = $oUser->id;
        $oNote->town_id = $aParams['town_id'];
        $oNote->poster_id = $aParams['poster_id'];
        $oNote->poster_name = $aParams['poster_name'];
        $oNote->message = json_encode(substr($aParams['message'], 0, 500));
        if ($PrimaryId != 0) {
          $oNote->note_id = $PrimaryId;
        }
        $oNote->save();
        if ($PrimaryId == 0) {
          $PrimaryId = $oNote->id;
          $oNote->note_id = $PrimaryId;
          $oNote->save();
        }
      }

      $aInsertedNote = array();
      if (isset($oNote)) {
        $aInsertedNote = $oNote->getPublicFields();
        $Created = $oNote->created_at;
        $Created->setTimezone($oWorld->php_timezone);
        $aInsertedNote['date'] = $Created->format('d-m-y H:i');
      }

      $aResponse = array(
        'note' => $aInsertedNote
      );

      ResponseCode::success($aResponse);

    } catch (Exception $e) {
      Logger::warning("API: Unable to add note. " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to add note.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function DeleteNotePOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'world', 'note_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Get indexes for player
      $aIndexList = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUserAndWorld($oUser, $aParams['world']);
      $aIndexKeys = array();
      foreach ($aIndexList as $oIndex) {
        $aIndexKeys[$oIndex->key_code] = $oIndex;
      }

      // Delete by note id
      $aNotes = \Grepodata\Library\Controller\IndexV2\Notes::allByKeysByNoteId(array_keys($aIndexKeys), $aParams['note_id']);
      foreach ($aNotes as $oNote) {
        $bDelete = false;
        if ($oNote->user_id == $oUser->id) {
          // Users can delete their own notes
          $bDelete = true;
        } elseif (in_array($aIndexKeys[$oNote->index_key]->role, array(
          \Grepodata\Library\Controller\IndexV2\Roles::ROLE_OWNER,
          \Grepodata\Library\Controller\IndexV2\Roles::ROLE_ADMIN,
        ))) {
          // admins can delete any note
          $bDelete = true;
        }

        if ($bDelete == true)  {
          $oNote->delete();
        }
      }

      ResponseCode::success(array());

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'Unable to delete note.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}