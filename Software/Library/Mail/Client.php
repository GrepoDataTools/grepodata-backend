<?php

namespace Grepodata\Library\Mail;

use Elasticsearch\ClientBuilder;
use Exception;
use Grepodata\Library\Controller\MailJobs;
use Grepodata\Library\Exception\InvalidEmailAddressError;
use Grepodata\Library\Logger\Logger;
use SendGrid\Mail\Mail;

class Client
{
  /**
   * Retry sending mail object
   * @param \Grepodata\Library\Model\MailJobs $oMail
   * @param bool $bUseSendGrid
   * @return int
   */
  public static function RetryMail($oMail, $bUseSendGrid = false)
  {
    return self::SendMail('admin@grepodata.com', $oMail->to_mail, $oMail->subject, $oMail->message, $oMail, true, $bUseSendGrid);
  }

  /**
   * @param string $From
   * @param string $To
   * @param string $Subject
   * @param string $Message
   * @param \Grepodata\Library\Model\MailJobs $oMail
   * @param bool $bCreateMailObject
   * @return bool|int
   * @throws InvalidEmailAddressError
   */
  public static function SendMail($From = 'admin@grepodata.com', $To = 'admin@grepodata.com',
                                  $Subject = '', $Message = '',
                                  $oMail = null, $bCreateMailObject = false, $bUseSendGrid = false)
  {
    try {
      if ($Message == '' || $Subject == '') {
        return false;
      }

      // Try to send mail
      if (!$bUseSendGrid) {
        $result = self::SendRequestSMTP($From, $To, $Subject, $Message);
      } else {
        $result = self::SendRequestSendGrid($From, $To, $Subject, $Message);
      }
      if ($result <= 0) {
        throw new \Exception("mailer result was <= 0");
      } else {
        Logger::warning("Good mail result: ".$result);
      }

      return $result;
    } catch (InvalidEmailAddressError $e) {
      // Don't create en email object for invalid receivers
      throw new InvalidEmailAddressError($e->getMessage());
    } catch (\Exception $e) {
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

  /**
   * Send an email using the SendGrid API
   * @param string $From
   * @param string $To
   * @param string $Subject
   * @param string $Message
   * @return int
   * @throws \SendGrid\Mail\TypeException
   */
  private static function SendRequestSendGrid($From = 'admin@grepodata.com', $To = 'admin@grepodata.com', $Subject = '', $Message = '')
  {
    $FromName = $From=='admin@grepodata.com'? 'GrepoData':'';

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
      $mail_result = "SendGrid MailResult: ".$response->statusCode()." {headers: ".json_encode($response->headers())."} [body: ".$response->body()."]";
      Logger::warning($mail_result);
      if (in_array($response->statusCode(), array(200, 202, 250))) {
        $Result = 1;
      }
    } catch (Exception $e) {
      Logger::warning("SendGrid Exception: ". $e->getMessage()." [".$e->getTraceAsString()."]");
      $Result = 0;
    }

    return $Result;
  }

  /**
   * Send an email via the SMTP client
   * @param string $From
   * @param string $To
   * @param string $Subject
   * @param string $Message
   * @return int
   * @throws InvalidEmailAddressError
   * @throws Exception
   */
  private static function SendRequestSMTP($From = 'admin@grepodata.com', $To = 'admin@grepodata.com', $Subject = '', $Message = '')
  {
    try {
      // Create the Transport
      $transport = (new \Swift_SmtpTransport(MAIL_TRANSPORT_HOST, 465, 'ssl'))
        ->setUsername(MAIL_TRANSPORT_NAME)
        ->setPassword(MAIL_TRANSPORT_KEY);

      // Create the Mailer using your created Transport
      $mailer = new \Swift_Mailer($transport);

      // Create a message
      $FromName = $From=='admin@grepodata.com'? 'GrepoData':'';
      $message = (new \Swift_Message($Subject))
        ->setFrom($From, $FromName)
        ->setTo($To)
        ->setContentType("text/html")
        ->setBody($Message);

      // Send the message
      return $mailer->send($message);
    } catch (Exception $e) {
      $EmailError = $e->getMessage();
      Logger::warning("Error sending mail with subject: ".$Subject.". Error: ".$EmailError);
      if (strpos($EmailError, 'does not comply with RFC') !== false
        || strpos($EmailError, 'must be a valid email address') !== false
      ) {
        // Invalid email, unable to create account with this email
        throw new InvalidEmailAddressError($e->getMessage());
      } else {
        // Throw other exceptions
        throw new Exception($e->getMessage());
      }
    }
  }
}
