<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Datetime;
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
use RuntimeException;

class RequestHandlerTest extends TestCase
{
    public static function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return Factory::createResponse();
    }

    public function testString()
    {
        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            Factory::createServerRequest()->withAttribute('request-handler', __CLASS__.'::handleRequest')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCustomAttribute()
    {
        $response = Dispatcher::run(
            [
                (new RequestHandler())->handlerAttribute('custom'),
            ],
            Factory::createServerRequest()->withAttribute('custom', __CLASS__.'::handleRequest')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvalidHandler()
    {
        $this->expectException(RuntimeException::class);

        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            Factory::createServerRequest()->withAttribute('custom', new Datetime())
        );
    }

    public function testCustomContainer()
    {
        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('IndexController', Argument::cetera())
            ->willReturn(new UtilsRequestHandler(function ($request) {
                return Factory::createResponse();
            }));

        $response = Dispatcher::run(
            [
                new RequestHandler($container->reveal()),
            ],
            Factory::createServerRequest()->withAttribute('request-handler', 'IndexController')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestHandler()
    {
        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            $request = Factory::createServerRequest()
                ->withAttribute('request-handler', new UtilsRequestHandler(function () {
                    return Factory::createResponse()->withHeader('X-Foo', 'Bar');
                }))
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testClosure()
    {
        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            $request = Factory::createServerRequest()
                ->withAttribute('request-handler', function () {
                    return Factory::createResponse()->withHeader('X-Foo', 'Bar');
                })
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }
}
