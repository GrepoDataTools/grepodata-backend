<?php

namespace Grepodata\Application\API\Route\Indexer;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Model\Indexer\Auth;
use Grepodata\Library\Router\BaseRoute;

class Owners extends \Grepodata\Library\Router\BaseRoute
{
  public static function ResetOwnersGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'mail', 'captcha'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // Validate ownership
      if ($aParams['mail'] !== $oIndex->mail) {
        die(self::OutputJson(array(
          'message'     => 'Invalid owner mail.',
          'parameters'  => $aParams
        ), 401));
      }

      Logger::warning("Reset owners request received for index " . $aParams['key']);

      // Create confirmation link
      $Token = md5(IndexBuilder::generateIndexKey(32) . time());
      $oAuth = new Auth();
      $oAuth->key_code = $oIndex->key_code;
      $oAuth->auth_token = $Token;
      $oAuth->action = 'reset_index_owner';
      $oAuth->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'Grepodata city indexer edit ownership',
        'Hi,<br/>
<br/>
You are receiving this message because you requested to reset the owners of your enemy city index. Please ignore this email if you no longer wish to execute this action.<br/>
<br/>
If you click on the following link, we will reset all owner data for your index <strong>'.$oIndex->key_code.'</strong> (world '.$oIndex->world.'):<br/>
<br/>
<a href="https://grepodata.com/indexer/action/'.$Token.'">Reset index owners ('.$oIndex->key_code.')</a><br/>
<br/>
If you did not request this email, please ignore it and consider changing your index key to a new and secure key.<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        false);

      // Failed sending mail
      if ($Result == 0) {
        return self::OutputJson(array(
          'status' => false
        ));
      }

      return self::OutputJson(array(
        'status' => true
      ));

    } catch (\Exception $e) {
      Logger::error("Critical error while resetting index owner . " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to reset owners.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function ExcludeAllianceGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'mail', 'captcha', 'alliance_id'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // Validate ownership
      if ($aParams['mail'] !== $oIndex->mail) {
        die(self::OutputJson(array(
          'message'     => 'Invalid owner mail.',
          'parameters'  => $aParams
        ), 401));
      }

      // Find alliance
      try {
        $oAlliance = Alliance::firstOrFail($aParams['alliance_id'], $oIndex->world);
      } catch (\Exception $e) {
        die(self::OutputJson(array(
          'message'     => 'Bad request, alliance does not exist.',
          'parameters'  => $aParams
        ), 400));
      }

      Logger::warning("Exclude alliance request received for index " . $aParams['key']);

      // Create confirmation link
      $Token = md5(IndexBuilder::generateIndexKey(32) . time());
      $oAuth = new Auth();
      $oAuth->key_code = $oIndex->key_code;
      $oAuth->auth_token = $Token;
      $oAuth->action = 'exclude_index_owner';
      $oAuth->data = json_encode(array(
        'alliance_id' => $oAlliance->grep_id,
        'alliance_name' => $oAlliance->name,
      ));
      $oAuth->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'Grepodata city indexer edit ownership',
        'Hi,<br/>
<br/>
You are receiving this message because you requested to edit the ownership of your enemy city index.<br/>
<br/>
If you click on the following link, we will remove the alliance <strong>'.$oAlliance->name.'</strong> from the list of owners for index <strong>'.$oIndex->key_code.'</strong> (world '.$oIndex->world.'):<br/>
<br/>
<a href="https://grepodata.com/indexer/action/'.$Token.'">Remove index owner ('.$oAlliance->name.')</a><br/>
<br/>
If you did not request this email, please ignore it and consider changing your index key to a new and secure key.<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        false);

      // Failed sending mail
      if ($Result == 0) {
        return self::OutputJson(array(
          'status' => false
        ));
      }

      return self::OutputJson(array(
        'status' => true
      ));

    } catch (\Exception $e) {
      Logger::error("Critical error while updating excluding index owner . " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to update owners.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function IncludeAllianceGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key', 'mail', 'captcha', 'alliance_id'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      // Validate ownership
      if ($aParams['mail'] !== $oIndex->mail) {
        die(self::OutputJson(array(
          'message'     => 'Invalid owner mail.',
          'parameters'  => $aParams
        ), 401));
      }

      // Find alliance
      try {
        $oAlliance = Alliance::firstOrFail($aParams['alliance_id'], $oIndex->world);
      } catch (\Exception $e) {
        die(self::OutputJson(array(
          'message'     => 'Bad request, alliance does not exist.',
          'parameters'  => $aParams
        ), 400));
      }

      Logger::warning("Include alliance request received for index " . $aParams['key']);

      // Create confirmation link
      $Token = md5(IndexBuilder::generateIndexKey(32) . time());
      $oAuth = new Auth();
      $oAuth->key_code = $oIndex->key_code;
      $oAuth->auth_token = $Token;
      $oAuth->action = 'include_index_owner';
      $oAuth->data = json_encode(array(
        'alliance_id' => $oAlliance->grep_id,
        'alliance_name' => $oAlliance->name,
      ));
      $oAuth->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'Grepodata city indexer edit ownership',
        'Hi,<br/>
<br/>
You are receiving this message because you requested to edit the ownership of your enemy city index.<br/>
<br/>
If you click on the following link, we will add the alliance <strong>'.$oAlliance->name.'</strong> to the list of owners for index <strong>'.$oIndex->key_code.'</strong> (world '.$oIndex->world.'):<br/>
<br/>
<a href="https://grepodata.com/indexer/action/'.$Token.'">Add index owner ('.$oAlliance->name.')</a><br/>
<br/>
If you did not request this email, please ignore it and consider changing your index key to a new and secure key.<br/>
<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        false);

      // Failed sending mail
      if ($Result == 0) {
        return self::OutputJson(array(
          'status' => false
        ));
      }

      return self::OutputJson(array(
        'status' => true
      ));

    } catch (\Exception $e) {
      Logger::error("Critical error while updating including index owner . " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to update owners.',
        'parameters'  => $aParams
      ), 500));
    }
  }

}