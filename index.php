<?php
/**
 * Front controller for AccessifyWiki API.
 *
 * @author Nick Freear, 11 February 2016.
 * @link http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/vendor/autoload.php';


$app = \AccessifyWiki\Api\App::getInstance();

$app->get('/', function (Request $request, Response $response) {

    $url = getenv('REDIRECT_URL');
    if ($url) {
        header('Location: '. $url);
        exit;
    } else {
        return $response->withStatus(500)->write('Woops! No redirect URL: '. $url);
    }
    //return $response->withRedirect(getenv('REDIRECT_URL'));
});

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->get('/fix', function (Request $request, Response $response, $args) use ($app) {

    $response = $response->withHeader('Access-Control-Allow-Origin', '*');

    $url = $app->getRequiredUrl($response);
    if (! $url) {
        //Error!
        return $response;
    }

    return $response->withJson([ 'stat' => 200, 'url' => $url ]);

    //return $app->json([ 'url' => $url ], $response);
});

$app->map(['GET', 'POST'], '/travis-hook', function (Request $request, Response $response, $args) use ($app) {

    $response = $response->withHeader('Content-Type', 'text/plain');

    $travis_hook = new \AccessifyWiki\Api\TravisWebHook();
    $result = $travis_hook->run();

    if ($travis_hook->shouldSkip()) {
        $response->getBody()->write('OK, SKIP request detected [aw skip]');

    } else if ($travis_hook->isTravisRequest()) {
        //OK.
        $response->getBody()->write('OK, this IS a travis request.');

        $github = new \AccessifyWiki\Api\GitHub();
        $resp = $github->getIndexJson();

        // DO MORE ....

    } else {
        $response->getBody()->write('Warning, NOT a travis request.');
    }

    $response->getBody()->write(print_r([
      //$request->getAttribute('route')->name(),
      //$request->getResourceUri(),
      $app->server('REQUEST_URI'),
      $_SERVER[ 'REQUEST_URI' ], $request->getMethod(),
      $request->getHeaderLine('Accept'), $request->getParsedBody() ], true));

    return $response;
});

$app->get('/fix-index.json', function ($request, $response, $args) {
    $github = new AccessifyWiki\Api\GitHub();
    //$resp = $client->request('GET', getenv('FIX_INDEX_URL'));
    $resp = $github->getIndexJson();

    //$response->getBody()->write($resp->getParsedBody());

    $resp = $resp->withHeader('X-AW-Passthru', 1);

    return $resp;
});

$app->get('/fix/{fix_id}', function (Request $request, Response $response, $args) use ($app) {

    //$file = '_fixes/ou-news.md';
    $fix_id = $args[ 'fix_id' ];

    $github = new AccessifyWiki\Api\GitHub();
    $result = $github->getFix($fix_id);

    var_dump('FIX', $fix_id, $result->fix);

    //return $result->response;
});

$app->get('/github/diff', function (Request $request, Response $response, $args) use ($app) {

    $commit = '2e30ab485a08d30c0806c73c5e8501ddbe4009de';

    $github = new AccessifyWiki\Api\GitHub();
    $result = $github->getDiff($commit);

    return $response;
});


//require_once 'lib/p2.php';

$app->run();


#End.
