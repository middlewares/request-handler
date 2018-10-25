<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Datetime;
use Exception;
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
            Factory::createServerRequest('GET', '/')->withAttribute('request-handler', __CLASS__.'::handleRequest')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCustomAttribute()
    {
        $response = Dispatcher::run(
            [
                (new RequestHandler())->handlerAttribute('custom'),
            ],
            Factory::createServerRequest('GET', '/')->withAttribute('custom', __CLASS__.'::handleRequest')
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
            Factory::createServerRequest('GET', '/')->withAttribute('custom', new Datetime())
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
            Factory::createServerRequest('GET', '/')->withAttribute('request-handler', 'IndexController')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testArrayHandler()
    {
        $request = Factory::createServerRequest('GET', '/');
        $request = $request->withAttribute('request-handler', ['Middlewares\\Tests\\Controller', 'run']);

        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            $request
        );

        $this->assertSame('Ok', (string) $response->getBody());
    }

    public function testRequestHandler()
    {
        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            $request = Factory::createServerRequest('GET', '/')
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
            $request = Factory::createServerRequest('GET', '/')
                ->withAttribute('request-handler', function () {
                    return Factory::createResponse()->withHeader('X-Foo', 'Bar');
                })
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testContinueOnEmptyClosure()
    {
        $response = Dispatcher::run(
            [
                (new RequestHandler())->continueOnEmpty(),
                function () {
                    return 'Fallback';
                },
            ]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Fallback', (string) $response->getBody());
    }

    public function testThrowExceptionOnEmpty()
    {
        $response = Dispatcher::run(
            [
                function ($request, $next) {
                    try {
                        return $next->handle($request);
                    } catch (RuntimeException $e) {
                        return $e->getMessage();
                    }
                },

                new RequestHandler(),

                function () {
                    return 'Fallback';
                },
            ]
        );

        $this->assertSame('Empty request handler', (string) $response->getBody());
    }

    public function testThrowExceptionOnInvalidHandler()
    {
        $response = Dispatcher::run(
            [
                function ($request, $next) {
                    try {
                        return $next->handle($request);
                    } catch (Exception $e) {
                        return $e->getMessage();
                    }
                },

                new RequestHandler(),

                function () {
                    return 'Fallback';
                },
            ],
            $request = Factory::createServerRequest('GET', '/')
                ->withAttribute('request-handler', ['--invalid--'])
        );

        $this->assertSame('Invalid request handler: array', (string) $response->getBody());
    }
}
