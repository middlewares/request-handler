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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RequestHandlerTest extends TestCase
{
    use ProphecyTrait;

    public static function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return Factory::createResponse();
    }

    public function testString(): void
    {
        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            Factory::createServerRequest('GET', '/')->withAttribute('request-handler', __CLASS__.'::handleRequest')
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testCustomAttribute(): void
    {
        $response = Dispatcher::run(
            [
                (new RequestHandler())->handlerAttribute('custom'),
            ],
            Factory::createServerRequest('GET', '/')->withAttribute('custom', __CLASS__.'::handleRequest')
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testInvalidHandler(): void
    {
        $this->expectException(RuntimeException::class);

        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            Factory::createServerRequest('GET', '/')->withAttribute('custom', new Datetime())
        );
    }

    public function testCustomContainer(): void
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

        self::assertSame(200, $response->getStatusCode());
    }

    public function testArrayHandler(): void
    {
        $request = Factory::createServerRequest('GET', '/');
        $request = $request->withAttribute('request-handler', ['Middlewares\\Tests\\Controller', 'run']);

        $response = Dispatcher::run(
            [
                new RequestHandler(),
            ],
            $request
        );

        self::assertSame('Ok', (string) $response->getBody());
    }

    public function testRequestHandler(): void
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

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testClosure(): void
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

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testContinueOnEmptyClosure(): void
    {
        $response = Dispatcher::run(
            [
                (new RequestHandler())->continueOnEmpty(),
                function () {
                    return 'Fallback';
                },
            ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Fallback', (string) $response->getBody());
    }

    public function testThrowExceptionOnEmpty(): void
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

        self::assertSame('Empty request handler', (string) $response->getBody());
    }

    public function testThrowExceptionOnInvalidHandler(): void
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

        self::assertSame('Invalid request handler: array', (string) $response->getBody());
    }
}
