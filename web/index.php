<?php

$app       = require __DIR__ . '/../bootstrap.php';
$container = $app->getContainer();

use Slim\Http\Request;
use Slim\Http\Response;
use Deref\Exceptions\DerefException;

// Default (root) route - sets up the Angular controllers and defines the site appearance.
$app->get('/', function(Request $request) use ($container) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container['logger'];
    $logger->info('pageload', [$request->getServerParams()]);

    $config    = $container['deref.config'];
    $analytics = $config['analytics'] ?? '';
    $output    = <<<END
<html>
  <head>
    <title>Deref</title>
    <link href="https://unpkg.com/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="/css/app.css" rel="stylesheet"/>
  </head>
  <body ng-app="DerefApp">
    <div id="deref" ng-app="DerefApp.controllers" ng-controller="derefController" class="container">
      <div class="row">
        <div class="col-md-6">

        <div class="jumbotron">
          <h1 class="text-uppercase"><small>Deref</small><em class="text-lowercase text-muted">.link</em></h1>
          <p class="lead text-primary">Ever come across a suspicious short URL and wanted to know where it <strong>really</strong> goes?</p>
          <p>Paste it here and find out!</p>
          <form ng-submit="submitForm()" role="form">
            <input type="text" class="input input-medium form-control" name="url" id="derefUrl" ng-model="derefUrl" placeholder="http://" required>
            <button type="submit" class="btn btn-primary btn-medium pull-right">Show Where It Goes &raquo;</button>
            <button type="reset" class="btn btn-default btn-medium pull-right" id="clearBtn" onclick="$('div#error-alert').remove();">Clear</button>
          </form>
        </div>
        <div class="col-md-8">
        <p class="lead">Sample URLs</p>
        <p>Want to try some samples? We've got you covered. Copy and paste one of these to try it out:</p>
        <ul>
            <li>http://t.co/GsOQGWW7D4</li>
            <li>http://kevinboyd.ca</li>
        </ul>
        </div>
        </div>
        <div class="col-md-6">

        <div class="panel panel-primary results-box">
          <div class="panel-heading">
            <h3 class="panel-title">Result</h3>
          </div>
          <div class="panel-body">
            <p id="Hops">This URL has {{ derefResponse.route_log.length - 1 }} redirect hop(s) to the final destination.</p>
          </div>
          <ul class="list-group">
            <li class="list-group-item"><strong>Domain:</strong> {{ derefResponse.final_domain }}</li>
            <li class="list-group-item"><strong>Final URL:</strong> {{ derefResponse.final_url }}</li>
            <li class="list-group-item"><strong>Redirect Log:</strong>
            <ul class="list-group">
                <li class="list-group-item"
                    ng-repeat="route in derefResponse.route_log">{{ \$index }} - {{route}}</li>
            </ul>
            </li>
          </ul>
        </div>

        <div class="panel panel-info">
            <div class="panel-heading">Quick API Reference</div>
            <div class="panel-body">
                <h5 class="subheader">POST /deref</h5>
                <pre>curl -d"url=google.com" https://deref.link/deref</pre>
                Or:
                <pre>curl -H "Content-Type: application/json" \
     -d'{"url": "google.com"}' \
     https://deref.link/deref</pre>
                <h6>Parameters:</h6>
                <table class="table table-hover">
                <tbody>
                <tr>
                <td><strong>url</strong></td>
                <td><small>The URL-encoded link to dereference. HTTP or HTTPS.<br>
                <em>Leading "http://" can be omitted, but not for HTTPS.</em></small></td>
                </tr>
                </tbody>
                </table>
                <h6>Returns JSON: <small>(return value reformatted for readability)</small></h6>
                <pre>{
    "start_url":    "google.com",
    "final_url":    "http://www.google.com/",
    "final_domain": "www.google.com",
    "route_log": [
        "http://google.com",
        "http://www.google.com/"
    ]
}</pre>
            </div>
        </div>  
        </div>
      </div>
      <p class="text-center">a <a href="https://whateverthing.com">whateverthing</a> project</p>
    </div>
    <script src="https://unpkg.com/jquery@3.2.1/dist/jquery.slim.min.js"></script>
    <script src="https://unpkg.com/bootstrap@3.3.7/dist/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/angular@1.3.20/angular.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/services.js"></script>
    <script src="js/controllers.js"></script>
    <a href="https://github.com/beryllium/deref"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/a6677b08c955af8400f44c6298f40e7d19cc5b2d/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_gray_6d6d6d.png"></a>
    <!-- begin analytics -->
    $analytics
    <!-- end analytics -->
  </body>
</html>
END;

   return $output;
});

// Deref route - accepts a URL parameter and responds with the redirect log in JSON
$app->post('/deref', function(Request $request, Response $response) use ($container) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container['logger'];

    /** @var \Deref\Deref $deref */
    $deref = $container['deref'];

    $body   = $request->getParsedBody();
    $url    = $body['url'] ?? null;
    $start  = microtime(true);

    $logger->info('deref request', ['url' => $url]);

    try {
        $result = $deref->getRedirectLog($url);
        $logger->info(
            'deref response',
            ['url' => $url, 'result' => $result, 'time_taken' => number_format(microtime(true) - $start, 4)]
        );
    } catch (DerefException $e) {
        return $response->withJson(['error' => $e->getMessage()], 400);
    } catch (\Exception $e) {
        return $response->withJson(['error' => 'An unknown error occurred'], 500);
    }

    return $response->withJson([
        'start_url'    => $url,
        'final_url'    => end($result),
        'final_domain' => parse_url(end($result), PHP_URL_HOST),
        'route_log'    => $result,
    ], 200);
});

$app->run();
