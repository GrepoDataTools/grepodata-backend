<?php

namespace Grepodata\Library\Controller\Indexer;

class ReportId
{

  /**
   * @param $Index string Index key code
   * @param $Hash string Report identifier created by userscript
   * @return \Grepodata\Library\Model\Indexer\ReportId
   */
  public static function firstByIndexByHash($Index, $Hash)
  {
    return \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $Index, 'and')
      ->where('report_id', '=', $Hash)
      ->firstOrFail();
  }
}
