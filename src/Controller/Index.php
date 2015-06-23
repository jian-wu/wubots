<?php
namespace Orchard\CiUtils\Controller;

use Orchard\CiUtils\Payload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Index
{
    public function payload(Request $request)
    {
        if ($request->headers->has('x-github-event')) {
            $payload = new Payload($request->getContent(), $request->headers->get('x-github-event'));
            $payload->processPayload();
        }

        return new Response('Received payload.');
    }
}
