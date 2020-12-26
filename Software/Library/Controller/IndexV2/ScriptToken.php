<?php

namespace Grepodata\Library\Controller\IndexV2;

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
      'client' => $Client
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