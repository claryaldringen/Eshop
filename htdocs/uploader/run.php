<?php

require_once __DIR__ . '/../libs/Nette/loader.php';
require_once __DIR__ . '/libs/HttpPHPUnit/init.php';

NDebug::enable();	

$http = new HttpPHPUnit;
$http->coverage(__DIR__ . '/../app', __DIR__ . '/report');
$http->run(__DIR__ . '/unit');
