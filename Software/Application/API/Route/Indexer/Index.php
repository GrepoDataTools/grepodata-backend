<?php

namespace Grepodata\Application\API\Route\Indexer;

use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Indexer\IndexOwners;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Model\Indexer\Auth;
use Grepodata\Library\Model\Indexer\Stats;
use Grepodata\Library\Router\BaseRoute;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Index extends BaseRoute
{

  public static function StatsGET()
  {
    $oStats = Stats::orderBy('created_at', 'desc')
      ->first();

    if ($oStats == null) {
      die(self::OutputJson(array(
        'message'     => 'No stats found.',
      ), 404));
    }

    return self::OutputJson($oStats);
  }

  public static function GetWorldsGET()
  {
    $aServers = \Grepodata\Library\Controller\World::getServers();
    $aWorlds = \Grepodata\Library\Controller\World::getAllActiveWorlds();

    $aResponse = array();
    foreach ($aServers as $Server) {
      $aServer = array(
        'server'  => $Server
      );
      foreach ($aWorlds as $oWorld) {
        if (strpos($oWorld->grep_id, $Server) !== false) {
          $aServer['timezone'] = $oWorld->php_timezone;
          $aServer['worlds'][] = array(
            'id'    => $oWorld->grep_id,
            'val'   => substr($oWorld->grep_id, 2),
            'name'  => $oWorld->name,
          );
        }
      }
      $aServer['worlds'] = self::SortWorlds($aServer['worlds']);
      $aResponse[] = $aServer;
    }

    return self::OutputJson($aResponse);
  }

  private static function SortWorlds($aWorlds)
  {
    usort($aWorlds, function ($item1, $item2) {
      if ($item1['val'] == $item2['val']) return 0;
      return $item1['val'] < $item2['val'] ? 1 : -1;
    });
    return $aWorlds;
  }

  public static function IsValidGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key'));

      // Validate index key
      if (!Validator::IsValidIndex($aParams['key'])) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }

      return self::OutputJson(array(
        'valid' => true
      ));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No index overview found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function NewKeyRequestGET()
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

      Logger::warning("New valid key request received for index " . $aParams['key']);

      // Create confirmation link
      $Token = md5(IndexBuilder::generateIndexKey(32) . time());
      $oAuth = new Auth();
      $oAuth->key_code = $oIndex->key_code;
      $oAuth->auth_token = $Token;
      $oAuth->action = 'move_index';
      $oAuth->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'Grepodata city indexer key change',
        'Hi,<br/>
<br/>
You are receiving this message because you requested a new index key for your enemy city index (index <strong>'.$oIndex->key_code.'</strong>, world '.$oIndex->world.').<br/>
Please click the link below to confirm your request. If you do not wish to change your index key you can ignore this email.<br/>
<br/>
<a href="https://grepodata.com/indexer/action/'.$Token.'">Reset index key</a><br/>
<br/>
Please note:<br/>
- By clicking on this link you will disable the current index and move it to a new index.<br/>
- Your previously collected intel will be available under the new index key.<br/>
- You will receive an email containing the new index key after it has been created.<br/>
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
      Logger::error("Critical error while updating index key (".$oIndex->key_code."). " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to update key.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function CleanupRequestGET()
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

      Logger::warning("New cleanup request received for index " . $aParams['key']);

      // Create confirmation link
      $Token = md5(IndexBuilder::generateIndexKey(32) . time());
      $oAuth = new Auth();
      $oAuth->key_code = $oIndex->key_code;
      $oAuth->auth_token = $Token;
      $oAuth->action = 'cleanup_session';
      $oAuth->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'GrepoData city index authentication',
        'Hi,<br/>
<br/>
You are receiving this message because you requested a cleanup key for your enemy city index (index <strong>'.$oIndex->key_code.'</strong>, world '.$oIndex->world.').<br/>
Please click the link below to authenticate your request. After clicking on this link, you will be able to delete selected intel records.<br/>
<br/>
<a href="https://grepodata.com/indexer/action/'.$Token.'">Confirm authentication</a><br/>
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
      Logger::error("Critical error while generating cleanup key (".$oIndex->key_code."). " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to build cleanup key.',
        'parameters'  => $aParams
      ), 500));
    }
  }

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

      $aCitys = CityInfo::allByKey($aParams['key']);
      foreach ($aCitys as $oCity) {
        try {
          $oCity->poster_alliance_id = null;
          $oCity->save();
        } catch (\Exception $e) {
          Logger::error("Critical error while resetting owners for index (" . $oIndex->key_code . "). " . $e->getMessage());
        }
      }

      // Rebuild overview
      IndexOverview::buildIndexOverview($oIndex);

      return self::OutputJson(array(
        'status' => true
      ));

    } catch (\Exception $e) {
      Logger::error("Critical error while resetting index owners . " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to update owners.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function ForgotKeysRequestGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('mail', 'captcha'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Get by mail
      $aIndices = IndexInfo::allByMail($aParams['mail']);
      if ($aIndices === false || is_null($aIndices) || sizeof($aIndices) <= 0) {
        die(self::OutputJson(array(
          'message'     => 'Invalid owner mail.',
          'parameters'  => $aParams
        ), 401));
      }
      Logger::warning("Forgot keys request received for index " . $aParams['key']);

      // Build overview
      $ListHtml = "";
      foreach ($aIndices as $oIndex) {
        if ($oIndex->moved_to_index == null) {
          $ListHtml .= '- <a href="https://grepodata.com/indexer/'.$oIndex->key_code.'">'.$oIndex->key_code.'</a> (world: '.$oIndex->world.', last update: '.$oIndex->updated_at.')<br/>';
        }
      }

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oIndex->mail,
        'GrepoData forgotten index keys',
        'Hi,<br/>
<br/>
You are receiving this message because you requested a list of your index keys form grepodata.com.<br/>
<br/>
Here is a list of your indexes:<br/>
'.$ListHtml.'
<br/>
If you did not request this email, please ignore it (someone else may have entered your email address on our website).<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        true);

      // Failed sending mail
//      if ($Result == 0) {
//        return self::OutputJson(array(
//          'status' => false
//        ));
//      }

      return self::OutputJson(array(
        'status' => true
      ));

    } catch (\Exception $e) {
      Logger::error("Critical error while sending forgotten keys. " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to retrieve indices.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function ConfirmActionGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('token'));

      // TODO: confirm with captcha?

      // Check token
      try {
        $oAuth = \Grepodata\Library\Controller\Indexer\Auth::firstByToken($aParams['token']);
        if ($oAuth === null || $oAuth === false) {
          throw new ModelNotFoundException();
        }
      } catch (ModelNotFoundException $e) {
        die(self::OutputJson(array(
          'success'     => false,
          'message'     => 'Invalid action token!'
        ), 401));
      }

      // Validate index key
      $oIndex = Validator::IsValidIndex($oAuth->key_code);
      if ($oIndex === null || $oIndex === false) throw new Exception();
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      Logger::warning("Validating index auth for index ".$oIndex->key_code.": ".json_encode($oAuth));

      switch ($oAuth->action) {
        case 'cleanup_session':
        case 'cleanup_session_activated':
          // Activate token
          $oAuth->action = 'cleanup_session_activated';
          $oAuth->save();
          $oIndex->csa = $oAuth->auth_token;
          $oIndex->save();

          return self::OutputJson(array(
            'status' => true,
            'action' => 'cleanup_session_activated',
            'data' => json_decode($aParams['token']),
            'key' => $oIndex->key_code
          ));

          break;

        case 'move_index':
          // Build new index
          $oNewIndex = IndexBuilder::buildNewIndex($oIndex->world, $oIndex->mail);

          // Update city records to point to new index
          $Count = 0;
          $Failed = 0;
          $aCities = CityInfo::allByKey($oIndex->key_code);
          foreach ($aCities as $oCity) {
            $Count += 1;
            try {
              $oCity->index_key = $oNewIndex->key_code;
              $oCity->save();
            } catch (Exception $e) {
              // Most likely a key constraint violation, not sure how thats possible though
              $Failed += 1;
            }
          }
          Logger::debugInfo("Updated ".$Count." intel records");

          if ($Failed > 0) {
            Logger::error("There were some failures while moving to a new index: ".$Failed." fails out of ".$Count.". Old index: ".$oIndex->key_code.". New index: ".$oNewIndex->key_code);
          }

          // Update conquest records
          try {
            $aConquests = Conquest::allByIndex($oIndex);
            /** @var \Grepodata\Library\Model\Indexer\Conquest $oConquest */
            foreach ($aConquests as $oConquest) {
              $oConquest->index_key = $oNewIndex->key_code;
              $oConquest->save();
            }
          } catch (Exception $e) {
            Logger::error("Unable to move all conquests for new index " . $oNewIndex->key_code . ". " . $e->getMessage());
          }

          // Update owners record
          try {
            $oIndexOwners = IndexOwners::firstOrFail($oIndex->key_code);
            $oIndexOwners->key_code = $oNewIndex->key_code;
            $oIndexOwners->save();
          } catch (Exception $e) {
            Logger::warning("Unable to move index owners: " . $e->getMessage());
          }

          // Update old index status
          $oIndex->moved_to_index = $oNewIndex->key_code;
          $oIndex->save();
          $oAuth->delete();

          // Rebuild overviews
          IndexOverview::buildIndexOverview($oIndex);
          IndexOverview::buildIndexOverview($oNewIndex);

          // Send new index email
          $Result = Client::SendMail(
            'admin@grepodata.com',
            $oIndex->mail,
            'Grepodata city indexer key change',
            'Hi,<br/>
<br/>
You have requested to move your enemy city index to a new key. Please find the new index key below.<br/>
<br/>
Old index key: '.$oIndex->key_code.',<br/>
New index key: '.$oNewIndex->key_code.',<br/>
New index page: <a href="https://grepodata.com/indexer/'.$oNewIndex->key_code.'">grepodata.com/indexer/'.$oNewIndex->key_code.'</a><br/>
<br/>
Your previously collected intel will be available under the new index key.<br/>
All new reports will be redirected to the new index; you do not need to update the userscript.<br/>
<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
            null,
            true);

          return self::OutputJson(array(
            'status' => true,
            'action' => 'moved',
            'new_key' => $oNewIndex->key_code
          ));

          break;
        case 'include_index_owner':
          // Update owner data
          $oIndexOwners = IndexOwners::firstOrNew($oIndex->key_code);
          $Includes = json_decode($oIndexOwners->getOwnersIncluded());
          $Includes[] = json_decode($oAuth->data);
          $oIndexOwners->key_code = $oIndex->key_code;
          $oIndexOwners->world = $oIndex->world;
          $oIndexOwners->setOwnersIncluded($Includes);

          // remove from excluded if present
          $aExcluded = json_decode($oIndexOwners->getOwnersExcluded());
          if ($aExcluded != null && is_array($aExcluded)) {
            $aExcludedUpdate = array();
            foreach ($aExcluded as $aExclude) {
              $aExclude = (array)$aExclude;
              $bExcluded = true;
              foreach ($Includes as $Include) {
                $Include = (array)$Include;
                if ($Include['alliance_id'] == $aExclude['alliance_id']) {
                  $bExcluded = false;
                }
              }
              if ($bExcluded === true) {
                $aExcludedUpdate[] = $aExclude;
              }
            }
            $oIndexOwners->setOwnersExcluded($aExcludedUpdate);
          }

          // Save
          $oIndexOwners->save();
          $oAuth->delete();

          // Rebuild overview
          IndexOverview::buildIndexOverview($oIndex);

          return self::OutputJson(array(
            'status' => true,
            'action' => 'include_index_owner',
            'data' => json_decode($oAuth->data),
            'key' => $oIndex->key_code
          ));

          break;

        case 'exclude_index_owner':
          // Update owner data
          $oIndexOwners = IndexOwners::firstOrNew($oIndex->key_code);
          $Excludes = json_decode($oIndexOwners->getOwnersExcluded());
          $Excludes[] = json_decode($oAuth->data);
          $oIndexOwners->key_code = $oIndex->key_code;
          $oIndexOwners->world = $oIndex->world;
          $oIndexOwners->setOwnersExcluded($Excludes);

          // remove from included if present
          $aIncluded = json_decode($oIndexOwners->getOwnersIncluded());
          if ($aIncluded != null && is_array($aIncluded)) {
            $aIncludedUpdate = array();
            foreach ($aIncluded as $aInclude) {
              $aInclude = (array)$aInclude;
              $bIncluded = true;
              foreach ($Excludes as $Exclude) {
                $Exclude = (array)$Exclude;
                if ($Exclude['alliance_id'] == $aInclude['alliance_id']) {
                  $bIncluded = false;
                }
              }
              if ($bIncluded === true) {
                $aIncludedUpdate[] = $aInclude;
              }
            }
            $oIndexOwners->setOwnersIncluded($aIncludedUpdate);
          }

          // Save
          $oIndexOwners->save();
          $oAuth->delete();

          // Rebuild overview
          IndexOverview::buildIndexOverview($oIndex);

          return self::OutputJson(array(
            'status' => true,
            'action' => 'exclude_index_owner',
            'data' => json_decode($oAuth->data),
            'key' => $oIndex->key_code
          ));

          break;

        case 'reset_index_owner':

          // Reset tracker data
          $aCitys = CityInfo::allByKey($oIndex->key_code);
          foreach ($aCitys as $oCity) {
            try {
              $oCity->poster_alliance_id = null;
              $oCity->save();
            } catch (\Exception $e) {
              Logger::error("Critical error while resetting owners for index (" . $oIndex->key_code . "). " . $e->getMessage());
            }
          }

          // Reset custom ownership
          try {
            $oIndexOwners = IndexOwners::firstOrNew($oIndex->key_code);
            $oIndexOwners->delete();
          } catch (Exception $e) {}

          $oAuth->delete();

          // Rebuild overview
          IndexOverview::buildIndexOverview($oIndex);

          return self::OutputJson(array(
            'status' => true,
            'action' => 'reset_index_owner',
            'key' => $oIndex->key_code
          ));

          break;

        default:
          throw new Exception(
            "No handler for index action: " . $oAuth->action);
          break;
      }

    } catch (\Exception $e) {
      Logger::error("Critical error while validating action auth (".$oIndex->key_code."). " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to validate action.',
        'parameters'  => $aParams
      ), 500));
    }
  }

  public static function GetIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key'));

      // Validate index key
      if ($aParams['key'] == "Jaegqam2") {
        $aParams['key'] = "jaegqam2";
      }
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

      $oIndexOverview = IndexOverview::firstOrFail($aParams['key']);
      if ($oIndexOverview == null) throw new ModelNotFoundException();

      $aResponse = array(
        'is_admin'          => false,
        'world'             => $oIndexOverview['world'],
        'total_reports'     => $oIndexOverview['total_reports'],
        'spy_reports'       => $oIndexOverview['spy_reports'],
        'enemy_attacks'     => $oIndexOverview['enemy_attacks'],
        'friendly_attacks'  => $oIndexOverview['friendly_attacks'],
        'latest_report'     => $oIndexOverview['latest_report'],
        'max_version'       => $oIndexOverview['max_version'],
        'latest_version'    => USERSCRIPT_VERSION,
        'update_message'    => USERSCRIPT_UPDATE_INFO,
        'owners'            => json_decode(urldecode($oIndexOverview['owners'])),
        'contributors'      => json_decode(urldecode($oIndexOverview['contributors'])),
        'alliances_indexed' => json_decode(urldecode($oIndexOverview['alliances_indexed'])),
        'players_indexed'   => json_decode(urldecode($oIndexOverview['players_indexed'])),
        'latest_intel'      => json_decode(urldecode($oIndexOverview['latest_intel'])),
      );

      if (isset($aParams['access_token'])) {
        $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], false);
        if ($oUser!=false && $oUser->is_confirmed==true && $oIndex->created_by_user == $oUser->id) {
          $aResponse['is_admin'] = true;
        }
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No index overview found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function NewIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'access_token', 'captcha'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Verify token
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // New index
      $oIndex = IndexBuilder::buildNewIndex($aParams['world'], $oUser->id);
      if ($oIndex !== false && $oIndex !== null) {
        //Logger::error('https://grepodata.com/indexer/' . $oIndex->key_code);

        $oIndex->created_by_user = $oUser->id;

        try {
          IndexOverview::buildIndexOverview($oIndex);
        } catch (\Exception $e) {
          Logger::error("Error building index overview for new index " . $oIndex->key_code . " (".$e->getMessage().")");
        }
        try {
          $Encrypted = md5($oIndex->key_code);

          $Result = Client::SendMail(
            'admin@grepodata.com',
            $oIndex->mail,
            'Grepodata city index '.$oIndex->world,
            'Hi,<br/>
<br/>
Thank you for your interest in our tools!<br/>
Please find your unique index key below.<br/>
<br/>
Index key: <strong>'.$oIndex->key_code.'</strong> (world '.$oIndex->world.'),<br/>
Index page: <a href="https://grepodata.com/indexer/'.$oIndex->key_code.'">grepodata.com/indexer/'.$oIndex->key_code.'</a>,<br/>
Userscript: <a href="https://api.grepodata.com/userscript/cityindexer_'.$Encrypted.'.user.js">cityindexer_'.$Encrypted.'.user.js</a><br/>
<br/>            
Please follow these instructions to start collecting your intel:<br/>
1. Install Tampermonkey for your browser<br/>
2. Install our cityindexer userscript (see link above)<br/>
3. Done! Restart your browser and start browsing your in-game forum as usual. The collected intel will start showing up on your index page after a few minutes.<br/>
<br/>
If you encounter any problems or have a question, please feel free to reply to this email.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
            null,
            true);

          Logger::debugInfo("Send mail result: " . json_encode($Result));
        } catch (\Exception $e) {
          Logger::error("Error sending new index email for index " . $oIndex->key_code . " (".$e->getMessage().")");
        }
        return self::OutputJson(array('status' => 'ok', 'key' => $oIndex->key_code));
      }
      else throw new \Exception();

    } catch (\Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'Error building new index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}