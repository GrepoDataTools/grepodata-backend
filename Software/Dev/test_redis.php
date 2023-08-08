<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

$Resource = "testzzz";
$Limit = 100;
$Window = 60;

$RateLimiter = \RateLimit\RateLimiterFactory::createRedisBackedRateLimiter([
  'host' => REDIS_HOST,
//  'host' => 'localhost',
  'port' => REDIS_PORT,
], $Limit, $Window);
try {
  $TTL = $RateLimiter->getResetAt($Resource) - time();
  $Attempts = $RateLimiter->getRemainingAttempts($Resource);
  $ttl_source = \Grepodata\Library\Redis\RedisClient::GetTTL($Resource);
  echo sprintf("TTL: $TTL\n");
  echo sprintf("TTL raw: $ttl_source\n");
  echo sprintf("Attempts: $Attempts\n");
//  $RateLimiter->hit($Resource);
  $t=2;
} catch (Exception $e) {
  print_r($e);
  $t=2;
}

//$Resource = 'testing';
//$Limit = 100;
//$Window = 60;
//
//$RateLimiter = \RateLimit\RateLimiterFactory::createRedisBackedRateLimiter([
//  'host' => REDIS_HOST,
//  'port' => REDIS_PORT,
//], $Limit, $Window);
//try {
//  $TTL = $RateLimiter->getResetAt($Resource) - time();
//  $Attempts = $RateLimiter->getRemainingAttempts($Resource);
//  echo sprintf("TTL: $TTL\n");
//  echo sprintf("Attempts: $Attempts\n");
//  $RateLimiter->hit($Resource);
//  $t=2;
//} catch (Exception $e) {
//  print_r($e);
//  $t=2;
//}
