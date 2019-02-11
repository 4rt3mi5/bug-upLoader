<?php

require_once __DIR__ . '/vendor/autoload.php';

$confPath = __DIR__ . '/app/config/';

$container = new \Slim\Container(['settings' => App::getSetting()]);

require __DIR__ . '/app/config/dependencies.php';

$task = new \YX\App\Client($container);
$task->run();





