<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ScriptToken
{

  /**
   * Create a new script token and return the uuid
   * @return \Grepodata\Library\Model\IndexV2\ScriptToken
   */
  public static function NewScriptToken() {
    return \Grepodata\Library\Model\IndexV2\ScriptToken::create(array(
      'token' => Str::uuid()
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