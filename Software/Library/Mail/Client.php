<?php

namespace Grepodata\Library\Mail;

use Elasticsearch\ClientBuilder;
use Exception;
use Grepodata\Library\Controller\MailJobs;
use Grepodata\Library\Logger\Logger;
use SendGrid\Mail\Mail;

class Client
{
  /**
   * Retry sending mail object
   * @param \Grepodata\Library\Model\MailJobs $oMail
   * @return int
   */
  public static function RetryMail($oMail)
  {
    return self::SendMail('admin@grepodata.com', $oMail->to_mail, $oMail->subject, $oMail->message, $oMail, true);
  }

  /**
   * @param string $From
   * @param string $To
   * @param string $Subject
   * @param string $Message
   * @param \Grepodata\Library\Model\MailJobs $oMail
   * @param bool $bCreateMailObject
   * @return bool|int
   */
  public static function SendMail($From = 'admin@grepodata.com', $To = 'admin@grepodata.com',
                                  $Subject = '', $Message = '',
                                  $oMail = null, $bCreateMailObject = false)
  {
    try {
      if ($Message == '' || $Subject == '') {
        return false;
      }

      // Try to send mail
      $result = self::SendRequest($From, $To, $Subject, $Message);
      if ($result <= 0) {
        throw new \Exception("mailer result was <= 0");
      }

      return $result;
    } catch (\Exception $e) {
      Logger::warning("Error sending mail with subject: ".$Subject.". Error: ".$e->getMessage());
      try {
        if ($oMail !== null || $bCreateMailObject == true) {
          // Save new mail object
          if ($oMail == null) {
            $oMail = MailJobs::AddMailJob($To, $Subject, $Message);
          }

          // Mark as failed
          $oMail->processing = false;
          $oMail->processed = false;
          $oMail->save();
        }
      } catch (\Exception $e) {
        Logger::error("Unable to save MailJob status to unprocessed: " . $e->getMessage());
      }
      return 0;
    }
  }

  private static function SendRequest($From = 'admin@grepodata.com', $To = 'admin@grepodata.com', $Subject = '', $Message = '')
  {
    $FromName = $From=='admin@grepodata.com'? 'GrepoData Admin':'';

    // Create a new mail
    $email = new Mail();
    $email->setFrom("admin@grepodata.com", $FromName);
    $email->setSubject($Subject);
    $email->addTo($To);
    $email->addContent(
      "text/html", "$Message"
    );

    // Connect via sendgrid
    $sendgrid = new \SendGrid(MAIL_WEB_API_KEY);
    $Result = 0;
    try {
      $response = $sendgrid->send($email);
      $mail_result = "MailResult: ".$response->statusCode()." {headers: ".$response->headers()."} [body: ".$response->body()."]";
      Logger::debugInfo($mail_result);
      if (in_array($response->statusCode(), array(200, 250))) {
        $Result = 1;
      }
    } catch (Exception $e) {
      Logger::warning("SendGrid Exception: ". $e->getMessage()." [".$e->getTraceAsString()."]");
      $Result = 0;
    }

    return $Result;
  }

  private static function SendRequestSMTP($From = 'admin@grepodata.com', $To = 'admin@grepodata.com', $Subject = '', $Message = '')
  {
    // Create the Transport
    $transport = (new \Swift_SmtpTransport(MAIL_TRANSPORT_HOST, 465, 'ssl'))
      ->setUsername(MAIL_TRANSPORT_NAME)
      ->setPassword(MAIL_TRANSPORT_KEY);

    // Create the Mailer using your created Transport
    $mailer = new \Swift_Mailer($transport);

    // Create a message
    $message = (new \Swift_Message($Subject))
      ->setFrom($From)
      ->setTo($To)
      ->setContentType("text/html")
      ->setBody($Message);

    // Send the message
    return $mailer->send($message);
  }
}
