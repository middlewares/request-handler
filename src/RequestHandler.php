<?php

namespace Middlewares;

use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\CallableResolver\CallableResolverInterface;
use Middlewares\Utils\CallableResolver\ReflectionResolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

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
     *
     * @param CallableResolverInterface $resolver
     */
    public function __construct(CallableResolverInterface $resolver = null)
    {
        if (empty($resolver)) {
            $resolver = new ReflectionResolver();
        }

        $this->resolver = $resolver;
    }

    /**
     * Set the attribute name to store handler reference.
     *
     * @param string $handlerAttribute
     *
     * @return self
     */
    public function handlerAttribute($handlerAttribute)
    {
        $this->handlerAttribute = $handlerAttribute;

        return $this;
    }

    /**
     * Extra arguments passed to the handler.
     *
     * @return self
     */
    public function arguments(...$args)
    {
        $this->arguments = $args;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $arguments = array_merge([$request], $this->arguments);

        $handler = $request->getAttribute($this->handlerAttribute);
        $callable = $this->resolver->resolve($handler, $arguments);

        return CallableHandler::execute($callable, $arguments);
    }
}
