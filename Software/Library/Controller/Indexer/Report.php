<?php

namespace Grepodata\Library\Controller\Indexer;

class Report
{

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\Indexer\Report
   */
  public static function firstById($Id)
  {
    return \Grepodata\Library\Model\Indexer\Report::where('id', '=', $Id)
      ->firstOrFail();
  }

}