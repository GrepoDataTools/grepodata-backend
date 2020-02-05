<?php

namespace Grepodata\Library\Controller;

class CronStatus
{
  /**
   * Return task by path
   * @param $Path string Filepath for cronjob
   * @return \Grepodata\Library\Model\CronStatus
   */
  public static function first($Path)
  {
    return \Grepodata\Library\Model\CronStatus::where('path', '=', $Path)
      ->first();
  }

  /**
   * Return task by path
   * @param $Path string Filepath for cronjob
   * @return \Grepodata\Library\Model\CronStatus
   * @throws \Exception
   */
  public static function firstOrFail($Path)
  {
    return \Grepodata\Library\Model\CronStatus::where('path', '=', $Path)
      ->firstOrFail();
  }

  /**
   * @param $Path string Filepath for cronjob
   * @return \Grepodata\Library\Model\CronStatus
   */
  public static function firstOrNew($Path)
  {
    return \Grepodata\Library\Model\CronStatus::firstOrNew(array(
      'path' => $Path
    ));
  }

}