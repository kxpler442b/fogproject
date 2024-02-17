<?php

namespace App\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

abstract class Preflight
{
    public function __invoke(ServerRequest $request, Response $response): Response
    {
        // Do nothing here. Just return the response.
        return $response;
    }
}