<?php

require __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = false; // set to true if problems arise

return $app;