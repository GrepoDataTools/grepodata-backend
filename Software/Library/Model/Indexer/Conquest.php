<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed index_key
 * @property mixed town_id
 * @property mixed town_name
 * @property mixed player_id
 * @property mixed player_name
 * @property mixed alliance_id
 * @property mixed alliance_name
 * @property mixed belligerent_player_id
 * @property mixed belligerent_player_name
 * @property mixed belligerent_alliance_id
 * @property mixed belligerent_alliance_name
 * @property mixed first_attack_date
 * @property mixed cs_killed
 * @property mixed new_owner_player_id
 * @property mixed belligerent_all
 * @property mixed total_losses_att
 * @property mixed total_losses_def
 * @property mixed num_attacks_counted
 */
class Conquest extends Model
{
  protected $table = 'Index_conquest';
}
