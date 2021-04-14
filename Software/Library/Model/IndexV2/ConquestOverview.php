<?php

namespace Grepodata\Library\Model\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Model\World;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
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

  public function getPublicFields()
  {
    $aBelligerentsAll = json_decode($this->belligerent_all, true);
    $aResponse = array(
      'conquest_id'   => $this->conquest_id,
      'index_key'     => $this->index_key,
      'num_attacks_counted' => $this->num_attacks_counted,
      'total_losses_att' => json_decode($this->total_losses_att, true),
      'total_losses_def' => json_decode($this->total_losses_def, true),
      'belligerent_all'  => is_array($aBelligerentsAll) ? array_values($aBelligerentsAll) : array()
    );

    try {
      $BPAtt = 0;
      foreach ($aResponse['total_losses_att'] as $Unit => $value) {
        $BPAtt += UnitStats::units[$Unit]['population'] * $value;
      }
      $BPDef = 0;
      foreach ($aResponse['total_losses_def'] as $Unit => $value) {
        $BPDef += UnitStats::units[$Unit]['population'] * $value;
      }
      $aResponse['total_bp_att'] = $BPAtt;
      $aResponse['total_bp_def'] = $BPDef;
    } catch (\Exception $e) {}

    return $aResponse;
  }
}
