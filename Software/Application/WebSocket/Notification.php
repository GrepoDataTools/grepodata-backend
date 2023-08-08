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

    // TODO: switch to non-blocking mysql interface https://github.com/friends-of-reactphp/mysql
    $oUser = User::GetUserById(1);

    // TODO: 1) REST API gives websocket ticket to userscript (/indexer/v2/getlatest)
    // TODO: 2) userscripts inits a websocket connection using the ticket
    // TODO: 3) validate websocket ticket against database and get teams for user
    // TODO: 4) if authenticated, connection subscribes to a topic for each team

    echo "New connection! ({$conn->resourceId}, {$oUser->username})\n";
  }

  public function onMessage(ConnectionInterface $from, $msg) {
    // In this application if clients send data it's because the user hacked around in console
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
