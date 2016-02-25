<?php
namespace AccessifyWiki\Api;

/**
 * Get various fix-related data from GitHub, including raw files, fixes, and diffs/patches.
 *
 * @author Nick Freear, 19 February 2016.
 */

use \GuzzleHttp\Client;

class GitHub
{
    # https://raw.githubusercontent.com/accessifywiki/accessifywiki.github.io/master/_fixes/example-fixes.md
    const FILE_URL = 'https://raw.githubusercontent.com/%s/%s/%s';
    const FIX_URL = 'https://raw.githubusercontent.com/%s/%s/_fixes/%s.md';

    # https://github.com/accessifywiki/accessifywiki.github.io/commit/2e30ab485a08d30c0806c73c5e8501ddbe4009de.patch
    const DIFF_URL = 'https://github.com/%s/commit/%s.patch';

    const DIFF_DATE = '';
    const DIFF_SUBJECT_REGEX = '/Subject: \[PATCH\] (?<subject>.+)/';
    const DIFF_SUMMARY_REGEX = '/ (?<files>\d+) files changed, (?<ins>\d+) insertions\(\+\), (?<del>\d+) deletions\(\-\)/';
    const DIFF_FILES_REGEX = '/ (?<file>[\w-_\/\.]+) \| (?<count>\d+) (?<add>[\+\-]+)/ms';

    private $http;

    public function __construct()
    {
        $this->http = new \GuzzleHttp\Client();
    }

    public function getDiff($commit, $slug = null)
    {
        $slug = $slug ? $slug : getenv('TRAVIS_REPO_SLUG');
        $url = sprintf(self::DIFF_URL, $slug, $commit);

        $response = $this->httpGet($url);
        $diff = null;

        if (200 === $response->getStatusCode()) {
            $diff = $this->parseDiff($response->getBody());
        }

        return (object) [
            'stat' => $response->getStatusCode(),
            'response' => $response,
            'diff' => $diff,
        ];
    }

    private function parseDiff($body)
    {
        $diff = new \StdClass;

        preg_match(self::DIFF_FILES_REGEX, $body, $m_files);
        preg_match(self::DIFF_SUMMARY_REGEX, $body, $m_summary);
        preg_match(self::DIFF_SUBJECT_REGEX, $body, $m_subject);

        var_dump('MATCH', $m_subject, $m_files);

        $diff->count_files = $m_summary[ 'files' ];
        $diff->count_inserts = $m_summary[ 'ins' ];
        $diff->count_deletes = $m_summary[ 'del' ];
        $diff->subject = $m_subject[ 'subject' ];

        return $diff;
    }

    public function getFile($file, $slug = null, $branch = 'master')
    {
        $slug = $slug ? $slug : getenv('TRAVIS_REPO_SLUG');
        $url = sprintf(self::FILE_URL, $slug, $branch, $file);

        $response = $this->httpGet($url);
        return $response;
    }

    public function getFix($fix_id, $slug = null, $branch = 'master')
    {
        $slug = $slug ? $slug : getenv('TRAVIS_REPO_SLUG');
        $url = sprintf(self::FIX_URL, $slug, $branch, $fix_id);

        $response = $this->httpGet($url);
        $fix = null;

        if (200 === $response->getStatusCode()) {
            $fix = new \StdClass();

            //$parser = new \Devster\Frontmatter\Parser('yaml', 'markdown');
            //$frontmatter = $parser->parse($response->getBody());
            $frontmatter = \KzykHys\FrontMatter\FrontMatter::parse($response->getBody());

            $fix->is_fix = isset($frontmatter[ 'layout' ]) ? 'fix' == $frontmatter[ 'layout' ] : false;
            $fix->id = $fix_id;
            $fix->title  = $frontmatter[ 'title' ];
            $fix->config = (object) $frontmatter[ 'x-aw-config' ];
            $fix->fixes  = $frontmatter[ 'x-aw-fixes' ];
            $fix->styles = $frontmatter[ 'x-aw-styles' ];
            $fix->description = $frontmatter->getContent();
        }

        return (object) [
            'ok'   => (boolean) $fix,
            'stat' => $response->getStatusCode(),
            'response' => $response,
            'fix'  => $fix,
        ];
    }

    public function getFixOldDevster($fix_id, $slug = null, $branch = 'master')
    {
        $slug = $slug ? $slug : getenv('TRAVIS_REPO_SLUG');
        $url = sprintf(self::FIX_URL, $slug, $branch, $fix_id);

        $response = $this->httpGet($url);
        $fix = null;

        if (200 === $response->getStatusCode()) {
            $fix = new \StdClass();

            $parser = new \Devster\Frontmatter\Parser('yaml', 'markdown');
            $frontmatter = $parser->parse($response->getBody());

            $fix->is_fix = isset($frontmatter->head[ 'layout' ]) ? 'fix' == $frontmatter->head[ 'layout' ] : false;
            $fix->id = $fix_id;
            $fix->title  = $frontmatter->head[ 'title' ];
            $fix->config = (object) $frontmatter->head[ 'x-aw-config' ];
            $fix->fixes  = $frontmatter->head[ 'x-aw-fixes' ];
            $fix->styles = $frontmatter->head[ 'x-aw-styles' ];
            $fix->description = $frontmatter->getBody();
        }

        return (object) [
            'ok'   => (boolean) $fix,
            'stat' => $response->getStatusCode(),
            'response' => $response,
            'fix'  => $fix,
        ];
    }

    public function getIndexJson()
    {
        $response = $this->httpGet(getenv('FIX_INDEX_URL'));
        return $response;
    }

    private function httpGet($url)
    {
        return $this->http->request('GET', $url);
    }
}

#End.
