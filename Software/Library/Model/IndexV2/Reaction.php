<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed reaction
 * @property mixed post_id
 * @property mixed thread_id
 * @property mixed index_key
 * @property mixed user_id
 * @property mixed player_id
 * @property mixed world
 */
class Reaction extends Model
{
  protected $table = 'Indexer_reaction';
  protected $fillable = array('index_key', 'thread_id', 'post_id', 'user_id', 'reaction');
}
