<?php
namespace AccessifyWiki\Api;

/**
 * Extend the Slim-based App with middleware, error handling, and utility functions.
 *
 * @author Nick Freear, 12 February 2016.
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class App extends \Slim\App
{
    const CALLBACK_REGEX = '/^[a-z_][\w\._]+$/';
    const URL_REGEX = '/^https?:\/\/\w+\.\w+/';

    private static $app;

    public static function getInstance()
    {
        if (! self::$app) {
            self::$app = new \AccessifyWiki\Api\App(self::setErrorHandler());
            self::$app->add(new \AccessifyWiki\Api\DotEnvMiddleware());
        }
        if (self::$app->isDebug()) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
        }
        return self::$app;
    }


    private static function setErrorHandler()
    {
        $c = new \Slim\Container();
        $c['errorHandler'] = function ($c) {
            return function ($request, $response, $exception) use ($c) {
                return $c['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write('Woops. Something went wrong! :(<p>')
                             ->write($exception->getMessage());
            };
        };
        return $c;
    }


    public function jsonOLD($obj, $response = null)  //OLD.
    {
        $response = $response ? $response : $this->response;  //???

        $callback = $this->getParam('callback');
        $callback = preg_match('/^[a-z_][\w\._]+$/', $callback) ? $callback : null;

        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');

        if ($callback) {
            $response->getBody()->write($callback .'('. json_encode($obj) .')');
        } else {
            $response->getBody()->write(json_encode($obj));
        }
        return $response;
    }

    public function isDebug()
    {
        return filter_input(INPUT_GET, 'debug', FILTER_SANITIZE_NUMBER_INT) > 0;
    }

    private function getValidCallback()
    {
        $callback = $this->getParam('callback');
        return $callback && preg_match(self::CALLBACK_REGEX, $callback) ? $callback : null;
    }

    public function getRequiredUrl(&$response)
    {
        $url = $this->getParam('url', FILTER_VALIDATE_URL);
        if (! $url) {
            //Error!
            $response = $response
                ->withJson([ 'stat' => 400, 'message' => 'Error. Bad request. The {url} parameter is required.' ])
                ->withStatus(400);
            ;
        }
        //$p = (object) parse_url($url);
        //if (! $p->host or ! $p->scheme) {
        if ($url && ! preg_match(self::URL_REGEX, $url)) {
            $response = $response
                ->withJson([ 'stat' => 400.2, 'message' => 'Error. Bad request. The {url} parameter is badly formed.', 'url' => $url ])
                ->withStatus(400);
            $url = false;
        }
        return $url;
    }

    public function getParam($key, $filter = FILTER_SANITIZE_STRING)
    {
        $value = filter_input(INPUT_GET, $key, $filter);

        return $value;
    }

    public function server($key, $filter = FILTER_SANITIZE_STRING)
    {
        return filter_input(INPUT_SERVER, $key, $filter);
    }
}
