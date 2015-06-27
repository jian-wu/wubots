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
        if ($request->headers->has('x-github-event') && $request->headers->has('x-hub-signature')) {
            $config = Yaml::parse(__DIR__ . '/../../configs/config.yml');
            $token  = $config['github_token'];
            $secret = $config['github_secret'];
            $body   = $request->getContent();

            $signature = "sha1=" . hash_hmac("sha1", $body, $secret);
            if (strcmp($signature, $request->headers->get('x-hub-signature')) !== 0) {
                throw new \Exception("Signatures didn't match!");
            }

            $payload = new Payload($body, $request->headers->get('x-github-event'), $token);
            $payload->processPayload();
        }

        return new Response('Received payload.');
    }
}
