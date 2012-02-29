<?php

require_once '../../libs/Nette/loader.php';
require_once '../../tests/libs/HttpPHPUnit/init.php';

NDebug::enable();	

$http = new HttpPHPUnit;
$http->coverage('../../app', '../../tests/report');
$http->run('../../tests/unit');
