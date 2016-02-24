<?php
namespace AccessifyWiki\Api;

/**
 * A fairly generic Travis-CI Web hook library.
 *
 * @author Nick Freear, 11 February 2016.
 * @link https://gist.github.com/nfreear/dd4d8e7adc6f3f3663f8
 * @link https://docs.travis-ci.com/user/notifications/#Webhook-notification
 */


/* # .htaccess file.

<IfModule mod_rewrite.c>

  RewriteEngine on

  # Make sure Authorization HTTP header is available to PHP
  # even when running as CGI or FastCGI.
  #
  RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

</IfModule>
*/


class TravisWebhook
{
    # https://docs.travis-ci.com/user/customizing-the-build/#Skipping-a-build
    const SKIP_REGEX   = '/\[(aw skip|skip aw)\]/';

    const REPO_SLUG    = 'accessifywiki/accessifywiki.github.io';
    const TRAVIS_TOKEN = '<EDIT ME>';  # <EDIT ME>
    const LOG_DIR      = './logs/';  # <EDIT ME>

    private $payload;
    private $auth;


    public function __construct()
    {
        #header( 'Content-Type: text/plain; charset=utf-8' );
    }

    public function run()
    {
        $hook_tests = (object) array(
            'time' => date('c'),
            #'request_time' => date( 'c', $this->server( 'REQUEST_TIME' )), # ???
            'remote_addr' => $this->server('REMOTE_ADDR'),  #IP
            'user_agent' => $this->server('HTTP_USER_AGENT'),
            'request_method' => $this->server('REQUEST_METHOD'),
            'is_valid_method' => $this->isValidRequestMethod(),
            'content_length' => $this->server('CONTENT_LENGTH'),
            'content_type' => $this->server('CONTENT_TYPE'),
            'is_valid_type' => $this->isValidContentType(),
            'slug' => $this->server('HTTP_TRAVIS_REPO_SLUG'),  #'Travis-Repo-Slug'
            'is_expected_slug' => $this->isExpectedRepoSlug(),
            'authorize' => $this->authorize(),
        );

        $hook_tests->data = $this->processPayload();
        $log_dir = getenv('TRAVIS_LOG_DIR');

        if ($hook_tests->slug) {
            $bytes = file_put_contents($log_dir . 'travis.log', json_encode($hook_tests), FILE_APPEND);
        } else {
            $bytes = file_put_contents($log_dir . 'not-travis.log', json_encode($hook_tests), FILE_APPEND);
        }

        /*if ($this->is_debug()) {
          var_dump( $bytes, $hook_tests );
        } else {
          echo 'OK';
        }*/
        return $hook_tests;
    }


    protected function processPayload()
    {
        $result = null;

        $payload_json = urldecode(filter_input(INPUT_POST, 'payload'));
        $payload = (object) json_decode($payload_json);

        $this->payload = $payload;

        if ($payload) {
            $result = (object) array(
                'travis_status' => $payload->status_message, #'Passed'
                'travis_code' => $payload->status,           # 0;
                'git_command' => $payload->type,             #'push'
                'start_time'  => $payload->started_at,       #'2016-02-11T12:29:45Z'
                'finish_time' => $payload->finished_at,
                'duration' => $payload->duration,            #118;
                'branch' => $payload->branch,
                'commit' => $payload->commit,
                'commit_message' => $payload->message,
                'commit_time' => $payload->committed_at,
                'commit_email' => $payload->committer_email,
            );
        }

        #if ($this->is_debug()) {
          #$result->_PAYLOAD_JSON => $payload_json;
          $result->_PAYLOAD = $payload;
          $result->_SERVER  = $_SERVER;
        #}
        return $result;
    }

    public function isTravisRequest()
    {
        return $this->isExpectedRepoSlug()
            && $this->isValidRequestMethod()
            && $this->isValidContentType()
            && $this->isAuthorized()
            && $this->isGitCommand('push')
            && $this->hasTravisStatus('Passed');
    }

    public function shouldSkip()
    {
        return $this->isTravisRequest()
            && preg_match(self::SKIP_REGEX, $this->payload->status_message);
    }

    protected function hasTravisStatus($status = 'Passed')
    {
        return $status === $this->payload->status;
    }

    protected function isGitCommand($command = 'push')
    {
        return $command === $this->payload->type;
    }

    protected function isAuthorized()
    {
        return $this->auth->is_authorized;
    }

    protected function authorize()
    {
        $result = (object) array(
            'auth_header' => $this->server('HTTP_AUTHORIZATION'),
            'auth_test' => hash('sha256', getenv('TRAVIS_REPO_SLUG') . getenv('TRAVIS_TOKEN')),
        );
        $result->is_authorized = $result->auth_header === $result->auth_test;
        $this->auth = $result;
        return $result;
    }

    protected function isExpectedRepoSlug()
    {
        return getenv('TRAVIS_REPO_SLUG') === $this->server('HTTP_TRAVIS_REPO_SLUG');
    }

    protected function isValidContentType()
    {
        return 'application/x-www-form-urlencoded' === $this->server('CONTENT_TYPE');
    }

    protected function isValidRequestMethod()
    {
        return 'POST' === $this->server('REQUEST_METHOD');
    }

    protected function server($key, $filter = FILTER_SANITIZE_STRING)
    {
        return filter_input(INPUT_SERVER, $key, $filter);
    }
}


#End.
