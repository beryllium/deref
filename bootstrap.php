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

return $app;