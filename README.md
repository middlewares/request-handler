# middlewares/request-handler

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to execute request handlers discovered by a router.

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)
* Optionally, a [PSR-11](https://github.com/php-fig/container) container to resolve the route handlers

## Installation

This package is installable and autoloadable via Composer as [middlewares/request-handler](https://packagist.org/packages/middlewares/request-handler).

```sh
composer require middlewares/request-handler
```

You may also want to install any route middleware like [middlewares/fast-route](https://packagist.org/packages/middlewares/fast-route) or [middlewares/aura-router](https://packagist.org/packages/middlewares/aura-router) for routing.

## Example

A routing middleware needs to be called before the request can be handled. In this example, we will use `fast-route` middleware.

```php
// Create the routing dispatcher
$fastRouteDispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->get('/hello/{name}', HelloWorldController::class);
});

$dispatcher = new Dispatcher([
    new Middlewares\FastRoute($fastRouteDispatcher),
    // ...
    new Middlewares\RequestHandler(),
]);

$response = $dispatcher->dispatch(new ServerRequest('/hello/world'));
```

When the request handler is invoked, it expects a request attribute to be defined that contains a reference to the handler. The reference will then be resolved and executed as a callable.

**This middleware should be the last middleware dispatched!** It does not call the delegate to continue processing.

If no resolver is provided, the reference will be interpreted as follows:

* If it's a string similar to `Namespace\Class::method`, and the method is not static, create a instance of `Namespace\Class` and call the method.
* If the string is the name of a existing class (like: `Namespace\Class`) and contains the method `__invoke`, create a instance and execute that method.
* Otherwise, treat it as a callable.

There are two options to change the default behavior:

- Inject a `Middlewares\Utils\CallableResolver\ContainerResolver` that wraps a [PSR-11 container](https://github.com/php-fig/container).
- Inject a `Middlewares\Utils\CallableResolver\CallableResolverInterface` instance that returns a callable.

```php
use Middlewares\Utils\CallableResolver\ContainerResolver;

// Use a PSR-11 container to load the handler
$resolver = new ContainerResolver($container);

$dispatcher = new Dispatcher([
    // ...
    new Middlewares\RequestHandler($resolver),
]);
```

## Options

### `__construct(Middlewares\Utils\CallableResolver\CallableResolverInterface $resolver)`

The resolver instance to use. If none is provided a generic `ReflectionResolver` will be used.

### `handlerAttribute(string $handlerAttribute)`

The attribute name used to get the handler reference in the server request. The default attribute name is `request-handler`.

### `arguments(...$args)`

Extra arguments to pass to the handler. This is useful to inject, for example a service container:

```php
$dispatcher = new Dispatcher([
    (new Middlewares\RequestHandler())
        ->arguments($app)
]);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/request-handler.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/request-handler/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/request-handler.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/request-handler.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/8afda09a-397a-4c80-9dc8-6edc081a03e3.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/request-handler
[link-travis]: https://travis-ci.org/middlewares/request-handler
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/request-handler
[link-downloads]: https://packagist.org/packages/middlewares/request-handler
[link-sensiolabs]: https://insight.sensiolabs.com/projects/8afda09a-397a-4c80-9dc8-6edc081a03e3
