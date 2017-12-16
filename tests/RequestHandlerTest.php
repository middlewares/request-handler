<?php

namespace Middlewares\Tests;

use Middlewares\RequestHandler;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Utils\RequestHandler as UtilsRequestHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandlerTest extends TestCase
{
    public function testString()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', __CLASS__.'::handleRequest');

        $response = Dispatcher::run([
            new RequestHandler(),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function handleRequest(ServerRequestInterface $request)
    {
        return Factory::createResponse();
    }

    public function testCustomContainer()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', 'IndexController');

        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('IndexController', Argument::cetera())->willReturn(new UtilsRequestHandler(function ($request) {
            return Factory::createResponse();
        }));

        $response = Dispatcher::run([
            new RequestHandler($container->reveal()),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestHandler()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', new UtilsRequestHandler(function () {
            return Factory::createResponse()->withHeader('X-Foo', 'Bar');
        }));

        $response = Dispatcher::run([
            new RequestHandler(),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }
}
