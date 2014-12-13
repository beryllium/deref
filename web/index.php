<?php

$app = require __DIR__ . '/../bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

// Default (root) route - sets up the Angular controllers and defines the site appearance.
$app->get('/', function() use ($app) {
    $config    = $app['deref.config'];
    $analytics = isset($config['analytics']) ? $config['analytics'] : '';
    $output    = <<<END
<html>
  <head>
    <title>Deref</title>
    <link href="/components/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="/css/app.css" rel="stylesheet"/>
  </head>
  <body ng-app="DerefApp">
    <div id="deref" ng-app="DerefApp.controllers" ng-controller="derefController" class="container">
      <div class="row">
        <div class="col-md-6">

        <div class="jumbotron">
          <h1 class="text-uppercase">*<small>Deref</small></h1>
          <p class="lead text-primary">Ever come across a suspicious short URL and wanted to know where it <strong>really</strong> goes?</p>
          <p>Paste it here and find out!</p>
          <form ng-submit="submitForm()" role="form">
            <input type="text" class="input input-medium form-control" name="url" id="derefUrl" ng-model="derefUrl" placeholder="http://" required>
            <button type="submit" class="btn btn-primary btn-medium pull-right">Show Where It Goes &raquo;</button>
            <button type="reset" class="btn btn-default btn-medium pull-right" id="clearBtn" onclick="$('div#error-alert').remove();">Clear</button>
          </form>
        </div>

        <p class="text-center">a <a href="http://whateverthing.com">whateverthing</a> project</p>
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

        </div>
      </div>
    </div>
    <script src="components/jquery/jquery.js"></script>
    <script src="components/bootstrap/js/bootstrap.min.js"></script>
    <script src="components/angularjs/angular.js"></script>
    <script src="components/angularjs/angular-route.js"></script>
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
$app->post('/deref', function(Request $request) use ($app) {
    $url = $request->get('url');

    try {
        $result = getRedirectLog($url);
    } catch (DerefException $e) {
        return new JsonResponse(['error' => $e->getMessage()], 400);
    } catch (Exception $e) {
        return new JsonResponse(['error' => 'An unknown error occurred'], 500);
    }

    return new JsonResponse([
        'start_url'    => $url,
        'final_url'    => end($result),
        'final_domain' => parse_url(end($result), PHP_URL_HOST),
        'route_log'    => $result,
    ]);
});

$app->run();

/**
 * Follow all the redirects of a URL and return an array containing all results.
 *
 * This is a recursive method that calls itself until the redirect chain is exhausted or else
 * the max recursion depth is reached (>10 redirects, in this case)
 *
 * @param  string   $url    URL to check
 * @param  int      $depth  (internal) Current recursion depth (starts at 0)
 *
 * @return array                        An array of URL matches
 * @throws TooManyRedirectsException    If recursion depth is exceeded
 * @throws InvalidUrlException          If URL fails validation
 * @throws CommunicationException       If a Curl error happens
 */
function getRedirectLog($url, $depth = 0)
{
    if ($depth > 10) {
        throw new TooManyRedirectsException('Too Many Redirects');
    }

    $url = filterUrl($url);

    $curl = curl_init($url);
    $opts = [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => false,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_NOBODY         => true,
        CURLOPT_USERAGENT      => 'deref', // fixes an issue with facebook redirecting to "/unsupported-browser"
    ];

    // Only allow valid SSL hosts
    // This could cause some consternation in the real world, due to the complexity of SSL configuration on servers
    // (both those running the code, and those being talked to by the code)
    if (parse_url($url, PHP_URL_SCHEME) == 'https') {
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        $opts[CURLOPT_SSL_VERIFYHOST] = 2;
    }

    curl_setopt_array($curl, $opts);

    $result   = curl_exec($curl);
    $redirect = curl_getinfo($curl, CURLINFO_REDIRECT_URL);

    if (!$result) {
        throw new CommunicationException(curl_error($curl));
    }

    // If this is a redirect response, that means we've got to go deeper
    // Recurse with a $depth + 1 to make sure we don't go too deep
    if ($redirect) {
        return (array_merge([$url], getRedirectLog($redirect, $depth + 1)));
    }

    return [$url];
}

/**
 * Filter URLs to ensure they are http:// or https://
 * If a URL does not provide a scheme, it will be given http://
 *
 * @param  string   $url        URL to filter
 *
 * @return string               Filtered URL
 * @throws InvalidUrlException  If the URL is unusable
 */
function filterUrl($url)
{
    // Ensure the URL is OK to work with
    $url_scheme = parse_url($url, PHP_URL_SCHEME);
    if (in_array($url_scheme, ['http', 'https'])) {
        return $url;
    } else if (!$url_scheme && 'http' == parse_url('http://' . $url, PHP_URL_SCHEME)) {
        return 'http://' . $url;
    }

    throw new InvalidUrlException('Invalid URL encountered in redirect chain');
}

class DerefException            extends Exception {};
class TooManyRedirectsException extends DerefException {}; // Too Many Redirects: https://www.youtube.com/watch?v=QrGrOK8oZG8
class InvalidUrlException       extends DerefException {};
class CommunicationException    extends DerefException {};