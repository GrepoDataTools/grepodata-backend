<?php

$url = "https://discord.com/api/webhooks/1084591254588903525/twlYiuUiPjaTV2gwtnHVBSZisjj70DtLmqKeQOxZkGRqLxkuRSOFbiDRAfvV_m0JY";

$headers = [ 'Content-Type: application/json; charset=utf-8' ];
$POST = [ 'username' => 'Testing BOT', 'content' => 'Testing message' ];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
$response   = curl_exec($ch);

echo $response;
