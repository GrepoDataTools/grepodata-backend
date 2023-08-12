<?php

namespace Grepodata\Application\WebSocket;

require('./../../config.php');

use Clue\React\Redis\Factory;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use React\EventLoop\Loop;

$loop  = Loop::get();

$notification_service = new Notification;

# Create redis backbone listener (messages from REST API will be transmitted over this channel)
$factory = new Factory($loop);
$redis = $factory->createLazyClient('localhost:'.REDIS_PORT);
$redis->subscribe("grepodata_backbone");
$redis->on('message', array($notification_service, 'onPush'));

# Create WebSocket server and set Notification as the event handler
$webSock = new SocketServer('0.0.0.0:8080', array(), $loop); // Binding to 0.0.0.0 means remotes can connect
$webServer = new IoServer(
  new HttpServer(
    new WsServer(
      $notification_service
    )
  ),
  $webSock
);

# Start the server
$loop->run();
