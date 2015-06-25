<?php
namespace Orchard\CiUtils\Controller;

use Orchard\CiUtils\Payload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

class Index
{
    public function payload(Request $request)
    {
        if ($request->headers->has('x-github-event')) {
            $config  = Yaml::parse(__DIR__ . '/../../configs/config.yml');
            $token   = $config['github_token'];
            $payload = new Payload($request->getContent(), $request->headers->get('x-github-event'), $token);
            $payload->processPayload();
        }

        return new Response('Received payload.');
    }
}
