<?php
namespace AccessifyWiki\Api;

/**
 * Load variables from a `.env` file, or test for required variables from `app.yaml`.
 *
 * @author Nick Freear, 12 February 2016.
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class DotEnvMiddleware
{

    static protected $REQUIRED_ENV = [ 'FIX_INDEX_URL', 'TRAVIS_REPO_SLUG', 'TRAVIS_TOKEN', 'TRAVIS_LOG_DIR' ];


    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        try {
            if (self::envIsTrue('aw_use_dotenv')) {
                $dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
                $dotenv->load();
                $dotenv->required(self::$REQUIRED_ENV);
            } else {
                $this->testGoogeAppEngineVariables();
            }
        } catch (Exception $ex) {
            header('HTTP/1.1 500');
            header('X-AW-Debug-00', 'dotenv = fail' . $ex->getMessage());
            echo 'Dotenv error! '. $ex->getMessage();
            exit;
        }

        $response = $next($request, $response);

        $response = $response->withHeader('X-AW-Debug-00', 'dotenv = ok');

        return $response;
    }

    /*
    http://stackoverflow.com/questions/2953646/how-to-declare-and-use-boolean-variables-in-shell-script
    */
    public static function envIsTrue($key)
    {
        $var = getenv($key);
        return 'false' !== strtolower($var) && 0 != $var;
    }

    private function testGoogeAppEngineVariables()
    {
        foreach (self::$REQUIRED_ENV as $var) {
            if (! getenv($var)) {
                //..
            }
        }
    }
}
