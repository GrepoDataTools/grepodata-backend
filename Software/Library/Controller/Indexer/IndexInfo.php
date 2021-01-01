<?php

namespace Grepodata\Library\Controller\Indexer;

use Grepodata\Library\Model\User;

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

  /**
   * Return all indexes that the user has rights on
   * @param User $oUser
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByUser(User $oUser)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::select(['Index_info.*', 'Indexer_roles.role', 'Indexer_roles.contribute', 'Indexer_roles.id AS sort_id'])
      ->join('Indexer_roles', 'Indexer_roles.index_key', '=', 'Index_info.key_code')
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->orderBy('Indexer_roles.id', 'desc')
      ->get();
  }

  /**
   * Return all indexes that the user has rights on within this world
   * @param User $oUser
   * @param $World
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByUserAndWorld(User $oUser, $World)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::select(['Index_info.*', 'Indexer_roles.role', 'Indexer_roles.contribute'])
      ->join('Indexer_roles', 'Indexer_roles.index_key', '=', 'Index_info.key_code')
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->where('Index_info.world', '=', $World)
      ->orderBy('Index_info.created_at', 'desc')
      ->get();
  }

}