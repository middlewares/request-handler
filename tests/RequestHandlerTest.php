<?php

namespace Middlewares\Tests;

use Middlewares\RequestHandler;
use Middlewares\Utils\CallableResolver\CallableResolverInterface;
use Middlewares\Utils\CallableResolver\ContainerResolver;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PHPUnit\Framework\TestCase;

class RequestHandlerTest extends TestCase
{
    public function testHandleCallable()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', function ($request) {
            return Factory::createResponse();
        });

        $response = Dispatcher::run([
            new RequestHandler(),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleInstance()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', [$this, 'handleRequest']);

        $response = Dispatcher::run([
            new RequestHandler(),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleReference()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', [__CLASS__, 'handleRequest']);

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
    public function handleRequest(ServerRequestInterface $request)
    {
        return Factory::createResponse();
    }

    public function testCustomResolver()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', 'IndexController');

        /** @var CallableResolverInterface|ObjectProphecy $resolver */
        $resolver = $this->prophesize(CallableResolverInterface::class);
        $resolver->resolve('IndexController', Argument::cetera())->willReturn(function ($request) {
            return Factory::createResponse();
        });

        $response = Dispatcher::run([
            new RequestHandler($resolver->reveal()),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContainerResolver()
    {
        $request = Factory::createServerRequest([], 'GET', '/');
        $request = $request->withAttribute('request-handler', 'IndexController');

        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('IndexController')->willReturn(function ($request) {
            return Factory::createResponse();
        });

        $resolver = new ContainerResolver($container->reveal());

        $response = Dispatcher::run([
            new RequestHandler($resolver),
        ], $request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
