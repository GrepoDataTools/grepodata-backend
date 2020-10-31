<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed to_mail
 * @property mixed subject
 * @property mixed message
 * @property mixed processing
 * @property mixed processed
 * @property mixed attempts
 */
class MailJobs extends Model
{
  protected $table = 'Mail_jobs';
  protected $fillable = array('to_mail', 'subject', 'message', 'processing', 'processed', 'attempts');
}
