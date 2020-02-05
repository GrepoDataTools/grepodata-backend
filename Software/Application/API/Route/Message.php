<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\MailJobs;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Logger\Pushbullet;
use Grepodata\Library\Router\BaseRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Message extends \Grepodata\Library\Router\BaseRoute
{
  public static function AddMessagePOST()
  {
    // Validate params
    $aParams = self::validateParams(array('mail', 'message', 'captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    // Save to db
    \Grepodata\Library\Controller\Message::AddMessage('_', $aParams['mail'], $aParams['message']);

    try {
      // Notify
      Pushbullet::SendPushMessage('New gd message: ' . $aParams['message']);

      // Mail job
      if (isset($aParams['mail']) && $aParams['mail']!='' && $aParams['mail']!='_') {
        MailJobs::AddMailJob('admin@grepodata.com', 'Re: Grepodata contact message',
        ''.$aParams['mail']." wrote on ".Carbon::now()->toDateTimeString().":\n
        ".$aParams['message']);
     }
    } catch (\Exception $e) {
      Logger::error("Error notifying contact message: " . $e->getMessage());
    }

    // Response
    $aResponse = array(
      'status'  => 'OK! Message added',
      'body'    => $aParams
    );

    return self::OutputJson($aResponse);
  }
}