<?php

namespace Grepodata\Library\Controller\Indexer;

use Illuminate\Database\Eloquent\Collection;

class Notes
{

  /**
   * @param \Grepodata\Library\Model\Indexer\IndexInfo $oIndex
   * @return Collection|\Grepodata\Library\Model\Indexer\Notes[]
   */
  public static function allByIndex(\Grepodata\Library\Model\Indexer\IndexInfo $oIndex)
  {
    return \Grepodata\Library\Model\Indexer\Notes::where('index_key', '=', $oIndex->key_code)
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param $Keys array list of Index identifiers
   * @param $Id int Town identifier
   * @return Collection|\Grepodata\Library\Model\Indexer\Notes[]
   */
  public static function allByTownIdByKeys($Keys, $Id)
  {
    return \Grepodata\Library\Model\Indexer\Notes::whereIn('index_key', $Keys, 'and')
      ->where('town_id', '=', $Id)
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param $Keys array list of Index identifiers
   * @param $PosterName string name of poster
   * @return Collection|\Grepodata\Library\Model\Indexer\Notes[]
   */
  public static function allByKeysByPoster($Keys, $PosterName)
  {
    return \Grepodata\Library\Model\Indexer\Notes::whereIn('index_key', $Keys, 'and')
      ->where('poster_name', '=', $PosterName)
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * @param $Keys array list of Index identifiers
   * @param $NoteId int note id
   * @return Collection|\Grepodata\Library\Model\Indexer\Notes[]
   */
  public static function allByKeysByNoteId($Keys, $NoteId)
  {
    return \Grepodata\Library\Model\Indexer\Notes::whereIn('index_key', $Keys, 'and')
      ->where('note_id', '=', $NoteId)
      ->orderBy('id', 'desc')
      ->get();
  }

}