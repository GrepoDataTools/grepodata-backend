<?php

namespace Grepodata\Library\Controller;

use Illuminate\Database\Eloquent\Model;

class Message
{

  public static function AddMessage($Name, $Mail, $Message, $Files = null)
  {
    $oMessage = new \Grepodata\Library\Model\Message();
    $oMessage->name     = $Name;
    $oMessage->mail     = $Mail;
    $oMessage->message  = $Message;
    $oMessage->files    = $Files;
    $oMessage->save();
  }
}
