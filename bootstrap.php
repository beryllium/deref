<?php

require __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = false; // set to true if problems arise

if (file_exists(__DIR__ . '/config.php')) {
    $config = include __DIR__ . '/config.php';
}

$app['deref.config'] = is_array($config) ? $config : [];

return $app;