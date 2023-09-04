<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed world
 * @property mixed team
 * @property mixed team_name
 * @property mixed user_id
 * @property mixed message
 * @property mixed action  # ops_event
 * @property mixed type  # notify_team, notify_user
 * @property mixed server_time
 */
class Notification extends Model
{
  protected $table = 'Indexer_notification';

  public function getPublicFields()
  {
    return array(
      'id'      => $this->id,
      'world'   => $this->world,
      'team'    => $this->team,
      'team_name' => $this->team_name,
      'user_id' => $this->user_id,
      'msg'     => json_decode($this->message, true),
      'action'  => $this->action,
      'type'    => $this->type,
      'date'    => $this->server_time,
    );
  }

}
