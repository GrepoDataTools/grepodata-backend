<?php

namespace Grepodata\Library\Controller;

class MailJobs
{

  public static function NextUnprocessedByIdAsc($PreviousId, $MaxAttempts = 5)
  {
    return \Grepodata\Library\Model\MailJobs::where('processed', '=', false, 'and')
      ->where('processing', '=', false, 'and')
      ->where('id', '>', $PreviousId, 'and')
      ->where('attempts', '<', $MaxAttempts)
      ->orderBy('id', 'asc')
      ->first();
  }

  public static function AddMailJob($To, $Subject, $Message)
  {
    $oMessage = new \Grepodata\Library\Model\MailJobs();
    $oMessage->to_mail    = $To;
    $oMessage->subject    = $Subject;
    $oMessage->message    = $Message;
    $oMessage->processed  = false;
    $oMessage->processing = false;
    $oMessage->save();
    return $oMessage;
  }
}