<?php

namespace Grepodata\Application\API\Http;

require('./../../../config.php');
require('./../config.api.php');

// Handle router requests
$oRouter = \Grepodata\Library\Router\Service::GetInstance();
$oRouter->Handle();
