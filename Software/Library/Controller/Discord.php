<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
use DateTimeZone;
use Grepodata\Library\Logger\Logger;

class Discord
{

  /**
   * @param $GuildId
   * @return \Grepodata\Library\Model\Discord
   */
  public static function firstOrNew($GuildId)
  {
    return \Grepodata\Library\Model\Discord::firstOrNew(array(
      'guild_id' => $GuildId
    ));
  }

  /**
   * @param $GuildId
   * @return \Grepodata\Library\Model\Discord
   */
  public static function firstOrFail($GuildId)
  {
    return \Grepodata\Library\Model\Discord::where('guild_id', '=', $GuildId)
    ->firstOrFail();
  }

  /**
   * @param $GuildId
   * @return \Grepodata\Library\Model\Discord Discord
   */
  public static function getGuildById($GuildId)
  {
    return \Grepodata\Library\Model\World::where('guild_id', '=', $GuildId)
      ->firstOrFail();
  }

}