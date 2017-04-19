<?php

namespace Middlewares;

use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\CallableResolver\CallableResolverInterface;
use Middlewares\Utils\CallableResolver\ReflectionResolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

class RequestHandler implements MiddlewareInterface
{
    /**
     * @var CallableResolverInterface Used to resolve the handlers
     */
    private $resolver;

    /**
     * @var string Attribute name for handler reference
     */
    private $attribute = 'request-handler';

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
     * @param string $attribute
     *
     * @return self
     */
    public function attribute($attribute)
    {
        $this->attribute = $attribute;

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
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $arguments = array_merge([$request], $this->arguments);

        $handler = $this->getHandler($request);
        $callable = $this->resolver->resolve($handler, $arguments);

        return CallableHandler::execute($callable, $arguments);
    }

    /**
     * Return the handler reference from the request.
     *
     * @param ServerRequestInterface $request
     *
     * @return callable|string|array
     */
    protected function getHandler(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->attribute);
    }
}
