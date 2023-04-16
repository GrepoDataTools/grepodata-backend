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
      ->orderBy('created_at', 'asc')
      ->get();
  }

  /**
   * @param $World string
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByWorld($World)
  {
    return \Grepodata\Library\Model\Indexer\IndexInfo::where('world', '=', $World)
      ->get();
  }

  /**
   * Return all indexes that the user has rights on
   * @param User $oUser
   * @param bool $bDoSort
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByUser(User $oUser, $bDoSort = true)
  {
    $Query = \Grepodata\Library\Model\Indexer\IndexInfo::select(['Indexer_info.*', 'Indexer_roles.role', 'Indexer_roles.contribute', 'Indexer_roles.id AS sort_id']);
    $Query->join('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_info.key_code');
    $Query->where('Indexer_roles.user_id', '=', $oUser->id);
    if ($bDoSort) {
      $Query->orderBy('Indexer_roles.id', 'desc');
    }
    return $Query->get();
  }

  /**
   * Return all indexes that the user has rights on within this world
   * @param User $oUser
   * @param $World
   * @param bool $bDoSort
   * @return \Grepodata\Library\Model\Indexer\IndexInfo[]
   */
  public static function allByUserAndWorld(User $oUser, $World, $bDoSort = true)
  {
    $Query = \Grepodata\Library\Model\Indexer\IndexInfo::select(['Indexer_info.*', 'Indexer_roles.role', 'Indexer_roles.contribute']);
    $Query->join('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_info.key_code');
    $Query->where('Indexer_roles.user_id', '=', $oUser->id);
    $Query->where('Indexer_info.world', '=', $World);
    if ($bDoSort) {
      $Query->orderBy('Indexer_info.created_at', 'desc');
    }
    return $Query->get();
  }

}
