<?php
namespace Grepodata\Application\WebSocket;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Logger\Logger;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


class Notification implements MessageComponentInterface {
  protected $clients;
  protected $redis;

  public function __construct() {
    try {
      $this->clients = new \SplObjectStorage;

      echo "finished setup\n";
    } catch (\Exception $e) {
      // TODO: we probably need a non-blocking logger tool (how to push alert to pushbullet?)
      Logger::error("CRITICAL: WebSocket startup failure: ".$e->getMessage() . " [".$e->getTraceAsString()."]");
    }
  }

  public function onPush($channel, $payload) {
    // pubsub message received on given $channel
    echo "message received on backbone: ". $payload ."\n";
  }

  public function onOpen(ConnectionInterface $conn) {
    // Store the new connection to send messages to later
    $this->clients->attach($conn);

    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $from, $msg) {
    // In this application if clients send data it's because the user hacked around in console

    try {
      $aData = json_decode($msg, true);
      if (key_exists('websocket_token', $aData)) {

        // TODO: this should all be done using async code...

//        // Check token
//        try {
//          $oToken = ScriptToken::GetScriptToken($aData['websocket_token']);
//        } catch (ModelNotFoundException $e) {
//          ResponseCode::errorCode(3041, array(), 401);
//        }
//
//        // Check expiration
//        $Limit = Carbon::now()->subDays(7);
//        if ($oToken->created_at < $Limit) {
//          // token expired
//          ResponseCode::errorCode(3042, array(), 401);
//        }
//
//        // Check client
//        if ($oToken->client !== $_SERVER['REMOTE_ADDR']) {
//          // Invalid client
//          Logger::warning("Remote mismatch during script token verification: ".$oToken->client.' != '.$_SERVER['REMOTE_ADDR']);
//          ResponseCode::errorCode(3043, array(), 401);
//        }

        // TODO: get teams for user
        // TODO: subscribe user connection to respective team topics

        return;
      }
    } catch (\Exception $e) {
      echo "Error authenticating client: " . $e->getMessage() . ' [' . $e->getTraceAsString() . ']';
    }

    // Invalid message, close connection
    $from->close();
  }

  public function onClose(ConnectionInterface $conn) {
    // The connection is closed, remove it, as we can no longer send it messages
    $this->clients->detach($conn);

    echo "Connection {$conn->resourceId} has disconnected\n";
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    echo "An error has occurred: {$e->getMessage()}\n";

    $conn->close();
  }
}
