<?php

namespace Grepodata\Library\Controller\Indexer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class Auth
{

  /**
   * @param $Token
   * @return \Grepodata\Library\Model\Indexer\Auth
   */
  public static function firstByToken($Token)
  {
    return \Grepodata\Library\Model\Indexer\Auth::where('auth_token', '=', $Token, 'and')
      ->where('updated_at', '>', Carbon::now()->subDays(31))
      ->firstOrFail();
  }

}