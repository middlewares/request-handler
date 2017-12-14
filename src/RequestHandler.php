<?php
declare(strict_types = 1);

namespace Middlewares;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\CallableResolver\CallableResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler implements MiddlewareInterface
{
    /**
     * @var CallableResolverInterface Used to resolve the handlers
     */
    private $resolver;

    /**
     * @var string Attribute name for handler reference
     */
    private $handlerAttribute = 'request-handler';

    /**
     * @var array Extra arguments passed to the handler
     */
    private $arguments = [];

    /**
     * Set the resolver instance.
     */
    public function __construct(CallableResolverInterface $resolver = null)
    {
        $this->resolver = $resolver;
    }

    /**
     * Set the attribute name to store handler reference.
     */
    public function handlerAttribute(string $handlerAttribute): self
    {
        $this->handlerAttribute = $handlerAttribute;

        return $this;
    }

    /**
     * Extra arguments passed to the handler.
     */
    public function arguments(...$args): self
    {
        $this->arguments = $args;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute($this->handlerAttribute);

        if ($requestHandler instanceof MiddlewareInterface) {
            return $requestHandler->process($request, $handler);
        }

        if ($requestHandler instanceof RequestHandlerInterface) {
            return $requestHandler->handle($request);
        }

        $callable = new CallableHandler($requestHandler, $this->arguments, $this->resolver);

        return $callable->handle($request);
    }
}
