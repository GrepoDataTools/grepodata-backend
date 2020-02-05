<?php
//
//require(__DIR__ . '/../config.php');
//
//echo 'mailtest!' . PHP_EOL;
//
//// Create the Transport
//$transport = (new Swift_SmtpTransport(PRIVATE_MAIL_TRANSPORT_HOST, 465, 'ssl'))
//  ->setUsername(PRIVATE_MAIL_TRANSPORT_NAME)
//  ->setPassword(PRIVATE_MAIL_TRANSPORT_KEY)
//;
//
//// Create the Mailer using your created Transport
//$mailer = new Swift_Mailer($transport);
//
////$logger = new Swift_Plugins_Loggers_EchoLogger();
////$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
//
//// Create a message
//$message = (new Swift_Message('Hi and welcome!'))
//  ->setFrom('admin@grepodata.com')
//  ->setTo('admin@grepodata.nl')
//  ->setBody('Hi and welcome!')
//;
//
//// Send the message
//$result = $mailer->send($message);
//
//echo json_encode($result);
