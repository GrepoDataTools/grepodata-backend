<?php

namespace Grepodata\Library\Controller\Indexer;

class IndexInfo
{

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexInfo
   */
  public static function first($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::where('key_code', '=', $Key)
      ->first();
  }

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexInfo
   */
  public static function firstOrFail($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::where('key_code', '=', $Key)
      ->firstOrFail();
  }

  /**
   * @param $Mail string
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByMail($Mail)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::where('mail', '=', $Mail)
      ->orderBy('created_at', 'desc')
      ->get();
  }

}