<?php

namespace Grepodata\Library\Model\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Model\World;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed uid
 * @property mixed conquest_id
 * @property mixed index_key
 * @property mixed belligerent_all
 * @property mixed total_losses_att
 * @property mixed total_losses_def
 * @property mixed num_attacks_counted
 */
class ConquestOverview extends Model
{
  protected $table = 'Indexer_conquest_overview';
  protected $fillable = array('conquest_id', 'index_key');
}
