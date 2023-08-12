<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\User;
use Illuminate\Support\Str;

class ScriptToken
{

  /**
   * Create a new script token and return the uuid
   * @param $Client
   * @return \Grepodata\Library\Model\IndexV2\ScriptToken
   */
  public static function NewScriptToken($Client) {
    return \Grepodata\Library\Model\IndexV2\ScriptToken::create(array(
      'token' => Str::uuid(),
      'client' => $Client,
      'user_id' => null,
      'payload' => null,
      'type' => 'st'
    ));
  }

  /**
   * Create a new websocket token and return the uuid
   * @param $Client
   * @param User $oUser
   * @param array $aPayload
   * @return \Grepodata\Library\Model\IndexV2\ScriptToken
   */
  public static function NewWebSocketToken($Client, User $oUser, array $aPayload=array()) {
    return \Grepodata\Library\Model\IndexV2\ScriptToken::create(array(
      'token' => Str::uuid(),
      'client' => $Client,
      'user_id' => $oUser->id,
      'payload' => json_encode($aPayload),
      'type' => 'ws'
    ));
  }

  /**
   * Get script token by token id
   * @param $Token
   * @return \Grepodata\Library\Model\IndexV2\ScriptToken
   */
  public static function GetScriptToken($Token) {
    return \Grepodata\Library\Model\IndexV2\ScriptToken::where('token', '=', $Token)
      ->firstOrFail();
  }

}
