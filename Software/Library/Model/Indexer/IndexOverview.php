<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed spy_reports
 * @property mixed friendly_attacks
 * @property mixed enemy_attacks
 * @property mixed total_reports
 * @property mixed owners
 * @property mixed contributors
 * @property mixed latest_intel
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
}
