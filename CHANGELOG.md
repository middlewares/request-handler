# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [0.5.0] - 2017-12-16

### Added

- Support for handlers implementing `MiddlewareInterface`.
- Support for handlers implementing `RequestHandlerInterface`.

### Changed

- Changed the constructor signature: Instead a `Middlewares\Utils\CallableResolverInterface`, now only `Psr\Container\ContainerInterface` is accepted.

### Removed

- Removed `arguments()` option
- Removed support for `callable` handlers. They must implement `RequestHandlerInterface` or `MiddlewareInterface`.

## [0.4.0] - 2017-11-13

### Changed

- Replaced `http-interop/http-middleware` with  `http-interop/http-server-middleware`.

### Removed

- Removed support for PHP 5.x.

## [0.3.0] - 2017-09-21

### Changed

- Append `.dist` suffix to phpcs.xml and phpunit.xml files
- Changed the configuration of phpcs and php_cs
- Upgraded phpunit to the latest version and improved its config file
- Updated to `http-interop/http-middleware#0.5`

## [0.2.0] - 2017-04-20

### Changed

- Renamed `attribute()` to `handlerAttribute()`

### Fixed

- Improved docs and examples in README

## 0.1.0 - 2017-04-19

First version

[0.5.0]: https://github.com/middlewares/request-handler/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/middlewares/request-handler/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/request-handler/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/request-handler/compare/v0.1.0...v0.2.0
