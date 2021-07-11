<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed key_code
 * @property mixed world
 * @property mixed owners
 * @property mixed contributors
 * @property mixed contributors_actual
 * @property mixed latest_report
 * @property mixed max_version
 * @property mixed alliances_indexed
 * @property mixed players_indexed
 * @property mixed latest_intel
 * @property mixed total_reports
 * @property mixed spy_reports
 * @property mixed enemy_attacks
 * @property mixed friendly_attacks
 */
class IndexOverview extends Model
{
  protected $table = 'Index_overview';
  protected $fillable = ['key_code', 'world'];

  public function getPublicFields()
  {
    return array(
      'owners'        => json_decode($this->owners, true),
      'contributors'  => json_decode($this->contributors, true),
      'latest_intel'  => json_decode($this->latest_intel, true),
      'total_reports' => $this->total_reports,
      'spy_reports'   => $this->spy_reports,
      'enemy_attacks' => $this->enemy_attacks,
      'friendly_attacks' => $this->friendly_attacks,
    );
  }

  public function getMinimalFields()
  {
    return array(
      'owners'        => json_decode($this->owners, true),
      'latest_report' => $this->latest_report,
      'total_reports' => $this->total_reports,
      'spy_reports'   => $this->spy_reports,
      'enemy_attacks' => $this->enemy_attacks,
      'friendly_attacks' => $this->friendly_attacks,
    );
  }
}
