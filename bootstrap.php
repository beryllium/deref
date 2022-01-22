<?php

use Beryllium\Cache\Client\ApcuClient;
use Beryllium\Cache\Client\MemoryClient;
use Beryllium\Cache\Wrapper\CascadeWrapper;

require __DIR__ . '/vendor/autoload.php';

$app       = new \Slim\App;
$container = $app->getContainer();

$container['debug']               = false;
$container['displayErrorDetails'] = false;

if (file_exists(__DIR__ . '/config.php')) {
    $config = include __DIR__ . '/config.php';
}

$container['deref.config'] = is_array($config) ? $config : [];

$container['logger'] = function ($c) {
    $logger       = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler(__DIR__ . '/logs/app.log');
    $logger->pushHandler($file_handler);

    return $logger;
};

// Caching
$container['cache'] = function ($c) {
    $apcuClient = new ApcuClient();
    $memoryClient = new MemoryClient();

    $clients = [];

    if (apcu_enabled()) {
        $clients[] = $apcuClient;
    }

    $clients[] = $memoryClient;

    $cache = new Beryllium\Cache\Cache(new CascadeWrapper(...$clients));
    $cache->setTtl(300);
    $cache->setPrefix('deref-');

    return $cache;
};

// Deref application service
$container['deref'] = function ($c) {
    $deref = new \Deref\Deref();
    $deref->setCache($c['cache']);

    $logger = $c['logger'];

    $deref->setLogger($logger);

    return $deref;
};

return $app;