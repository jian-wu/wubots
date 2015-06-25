<?php
namespace Orchard\CiUtils;

use GuzzleHttp\Client as HttpClient;

class Payload
{
    private $payload;
    private $baseUrl = 'https://api.github.com';
    private $defaultConfig;
    private $headers;
    private $ciContext = 'PHPCS Style Check';
    private $ciStatusDescriptions = array(
        'pending' => 'Style check commenced.',
        'error'   => 'Style check reported violations.',
        'success' => 'Style check passed.',
    );

    protected $issueComment;
    protected $pullRequest;
    protected $eventType;

    public function __construct($data, $eventType, $token)
    {
        $this->defaultConfig = array('base_uri' => $this->baseUrl);
        $this->headers = array(
            'Authorization' => 'token ' . $token,
            'User-Agent'    => 'Wubots - PHPCS',
            'Content-Type'  => 'application/json',
        );
        $this->eventType = $eventType;
        $this->setPayload($data);
    }

    public function setPayload($data)
    {
        if ($payload = json_decode($data, true)) {
            $this->payload = $payload;
        }

        switch ($this->eventType) {
            case PayloadEventType::PULL_REQUEST:
                $this->setPullRequest($this->payload['pull_request']);
                break;
            case PayloadEventType::ISSUE_COMMENT:
                $this->setIssueComment();
                break;
            default:
                // do nothing
        }
    }

    public function getPullRequest()
    {
        return $this->pullRequest;
    }

    protected function setPullRequest($pullRequest)
    {
        $this->pullRequest = $pullRequest;
    }

    protected function setIssueComment()
    {
        $this->issueComment = $this->payload['comment']['body'];
    }

    public function processPayload()
    {
        if ($this->eventType == PayloadEventType::PULL_REQUEST) {
            $this->processPullRequest();
        } elseif ($this->eventType == PayloadEventType::ISSUE_COMMENT) {
            $this->processIssueComment();
        }
    }

    protected function processPullRequest()
    {
        $action = $this->payload['action'];
        $state  = $this->pullRequest['state'];
        $uri    = $this->getPullRequestUri();

        if ($state == 'open' && in_array($action, array('opened', 'synchronize'))) {
            $this->setBuildStatus($uri, 'pending');
        }
    }

    protected function getPullRequestUri() {
        $fullName = $this->pullRequest['base']['repo']['full_name'];
        $sha      = $this->pullRequest['head']['sha'];

        return '/repos/' . $fullName . '/statuses/' . $sha;
    }

    protected function processIssueComment()
    {
        if (stristr($this->issueComment, 'recheck styles')) {
            $pullRequestUrl = $this->payload['issue']['pull_request']['url'];
            $pullRequest    = json_decode($this->queryGitHub($pullRequestUrl, 'get', '', ''), true);
            $this->setPullRequest($pullRequest);
            $uri = $this->getPullRequestUri();
            $this->setBuildStatus($uri, 'pending');
        }
    }

    protected function setBuildStatus($uri, $status)
    {
        $body = '{
            "state": "' . $status . '",
            "description": "' . $this->ciStatusDescriptions[$status] . '",
            "context": "' . $this->ciContext . '"
        }';

        $this->queryGitHub($uri, 'post', $body);

    }

    protected function queryGitHub($uri, $method, $body = null, $baseUrl = null)
    {
        $configs = $this->defaultConfig;

        if (!is_null($baseUrl)) {
            $configs = array_merge(array('base_uri' => $baseUrl), $configs);
        }

        $client = new HttpClient($configs);
        $res = $client->request(
            $method,
            $uri,
            array(
                'headers' => $this->headers,
                'body' => $body,
            )
        );

        if ($res->getStatusCode() % 200 > 10) {
            throw new \Exception($res->getBody());
        }

        return $res->getBody();
    }
}
