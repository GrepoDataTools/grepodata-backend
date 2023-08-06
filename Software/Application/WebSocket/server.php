<?php

namespace Grepodata\Application\WebSocket;

require('./../../config.php');

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Grepodata\Application\WebSocket\Chat;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new Chat()
    )
  ),
  8080
);

$server->run();
