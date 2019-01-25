<?php

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

// Deref application service
$container['deref'] = function ($c) {
    $deref = new \Deref\Deref();

    $logger = $c['logger'];

    $deref->setLogger($logger);

    return $deref;
};

return $app;