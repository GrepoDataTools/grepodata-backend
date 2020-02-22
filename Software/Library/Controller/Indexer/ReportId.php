<?php

namespace Grepodata\Library\Controller\Indexer;

class ReportId
{

  /**
   * @return boolean
   */
  public static function exists($Id, $Player, $Index)
  {
    $oUser = \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $Index, 'and')
      ->where('player_id', '=', $Player, 'and')
      ->where('report_id', '=', $Id)
      ->first();
    if ($oUser !== null) {
      return true;
    }
    return false;
  }

  /**
   * @param $Hash
   * @param $Index
   * @return \Grepodata\Library\Model\Indexer\ReportId
   */
  public static function getByHashIndex($Hash, $Index)
  {
    return \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $Index, 'and')
      ->where('report_id', '=', $Hash)
      ->first();
  }

  public static function latestByIndexKeyByPlayer($Index, $Player, $Limit = 50)
  {
    return \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $Index, 'and')
      ->where('index_type', '=', 'inbox', 'and')
      ->where('player_id', '=', $Player)
      ->orderBy('id', 'desc')
      ->limit($Limit)
      ->get();
  }
  public static function latestByIndexKey($Index, $Limit = 300)
  {
    return \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $Index, 'and')
      ->where('index_type', '=', 'forum')
      ->orderBy('id', 'desc')
      ->limit($Limit)
      ->get();
  }

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