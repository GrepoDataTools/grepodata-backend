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
    $aParams = self::validateParams(array());

    if (isset($aParams['data'])) {
      $aParams = json_decode($aParams['data'], true);
    }

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    // Parse optional file
    $aUploadedFiles = array();
    $bHasFiles = false;
    try {
      $aFiles = $_FILES;
      if (sizeof($aFiles)>0) {
        foreach ($aFiles as $aFile) {
          if ($aFile['error'] == UPLOAD_ERR_OK
            && is_uploaded_file($aFile['tmp_name'])
          ) {
            $bHasFiles = true;
            $Content = file_get_contents($aFile['tmp_name']);
            $Filename = $aFile['name'];
            $Filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $Filename);
            $Filename = mb_ereg_replace("([\.]{2,})", '', $Filename);
            $Filename = 'bug_' . time() . $Filename;
            file_put_contents(IMG_UPLOAD_DIR_REPORT . $Filename, $Content);
            $aUploadedFiles[] = array(
              'filename' => $aFile['name'],
              'type' => $aFile['type'],
              'path' => $Filename
            );
          }
        }
      }
    } catch (\Exception $e) {
      Logger::error("Error uploading bug report image: " . $e->getMessage());
    }

    $Message = $aParams['message'];
    try {
      $Message .= ' - ' . $_SERVER['HTTP_USER_AGENT'];
    } catch (\Exception $e) {}

    // Save to db
    \Grepodata\Library\Controller\Message::AddMessage($aParams['name']=='bug_report'?'bug_report':'_', $aParams['mail'], $Message, json_encode($aUploadedFiles));

    try {
      // Notify
      Logger::error('New gd message: ' . $aParams['message']);

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
