<?php

namespace Grepodata\Library\Controller;

use Illuminate\Database\Eloquent\Model;

class PlayerScoreboard
{

  /**
   * @param $Date string Scoreboard date (Y-m-d)
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard
   */
  public static function first($Date, $World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::where('date', '=', $Date, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $Date string Scoreboard date (Y-m-d)
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard
   */
  public static function firstOrFail($Date, $World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::where('date', '=', $Date, 'and')
      ->where('world', '=', $World)
      ->firstOrFail();
  }

  /**
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard
   */
  public static function latestByWorldOrFail($World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::where('world', '=', $World)
      ->orderBy('updated_at', 'desc')
      ->firstOrFail();
  }

  /**
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard
   */
  public static function yesterdayByWorld($World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::where('world', '=', $World)
      ->orderBy('updated_at', 'desc')
      ->skip(1)->take(1)->get();
  }

  /**
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard
   */
  public static function getMinDate($World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::where('world', '=', $World)
      ->orderBy('created_at', 'asc')
      ->firstOrFail();
  }

  /**
   * @param $Date string Scoreboard date (Y-m-d)
   * @param $World string World identifier
   * @return \Grepodata\Library\Model\PlayerScoreboard | Model
   */
  public static function firstOrNew($Date, $World)
  {
    return \Grepodata\Library\Model\PlayerScoreboard::firstOrNew(array(
      'date'    => $Date,
      'world'   => $World
    ));
  }

}