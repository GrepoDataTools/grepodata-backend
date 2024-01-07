<?php

namespace Grepodata\Library\Model\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Model\World;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed town_id
 * @property mixed world
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
 */
class Conquest extends Model
{
  protected $table = 'Indexer_conquest';

  /**
   * @param $oMixedConquest object A joined record consisting of both Conquest and ConquestOverview
   * @param World|null $oWorld
   * @return array
   */
  public static function getMixedConquestFields($oMixedConquest, World $oWorld = null)
  {
    $aBelligerentsAll = json_decode($oMixedConquest->belligerent_all, true);
    $aResponse = array(
      // Conquest fields
      'conquest_id'   => $oMixedConquest->id,
      'town_id'       => $oMixedConquest->town_id,
      'town_name'     => $oMixedConquest->town_name,
      'player_id'     => $oMixedConquest->player_id,
      'player_name'   => $oMixedConquest->player_name,
      'alliance_id'   => $oMixedConquest->alliance_id,
      'alliance_name' => $oMixedConquest->alliance_name,
      'last_attack_date' => $oMixedConquest->first_attack_date,
      'belligerent_player_id'     => $oMixedConquest->belligerent_player_id,
      'belligerent_player_name'   => $oMixedConquest->belligerent_player_name,
      'belligerent_alliance_id'   => $oMixedConquest->belligerent_alliance_id,
      'belligerent_alliance_name' => $oMixedConquest->belligerent_alliance_name,
      'new_owner_player_id' => $oMixedConquest->new_owner_player_id,
      'cs_killed' => $oMixedConquest->cs_killed,

      // ConquestOverview fields
      'conquest_uid'  => $oMixedConquest->uid,
      'index_key'     => $oMixedConquest->index_key,
      'published'     => $oMixedConquest->published == 1 ?? false,
      'num_attacks_counted' => $oMixedConquest->num_attacks_counted,
      'average_luck' => !is_null($oMixedConquest->total_luck) && is_numeric($oMixedConquest->total_luck) && $oMixedConquest->num_attacks_counted > 0 ? $oMixedConquest->total_luck / $oMixedConquest->num_attacks_counted : null,
      'total_losses_att' => json_decode($oMixedConquest->total_losses_att, true),
      'total_losses_def' => json_decode($oMixedConquest->total_losses_def, true),
      'belligerent_all'  => is_array($aBelligerentsAll) ? array_values($aBelligerentsAll) : array()
    );

    $aResponse['hide_details'] = false;
    if ($oWorld != null) {
      $Now = $oWorld->getServerTime();
      if ($oMixedConquest->new_owner_player_id == null
        && $oMixedConquest->cs_killed == false
        && $Now->diffInHours(Carbon::parse($oMixedConquest->first_attack_date, $oWorld->php_timezone)) < 4) {
        // add a 3 hour delay before friendly intel is visible in the detailed report
        $aResponse['hide_details'] = true;
      }
    }

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
