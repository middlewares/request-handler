<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Controller implements RequestHandlerInterface
{
    public function run(): ResponseInterface
    {
        $response = Factory::createResponse();
        $response->getBody()->write('Ok');

        return $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run();
    }
}
