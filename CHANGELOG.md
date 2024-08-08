# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### New Features

- New `AppBuilder` to create a Slim App instance for different scenarios. Replaces the `AppFactory`.
- Unified DI container resolution. All the factory logic has been removed and moved to the DI container.
- Add new `RoutingMiddleware` and the new `EndpointMiddleware` for better separation of concern and flexibility.
- Optimize middleware execution pipeline. Provide FIFO middleware order support. FIFO by default. Can be changed to LIFO using the AppBuilder.
- New `BasePathMiddleware` for dealing with Apache sub-directories.
- Simplified Error handling concept. Relates to #3287.
  - Separation of Exceptions handling, PHP Error handling and Exception logging into different middleware.
  - New custom error handlers using a new `ExceptionHandlerInterface`. See new `ExceptionHandlingMiddleware`.
  - New `ExceptionLoggingMiddleware` for custom error logging.
- Support to build a custom middleware pipeline without the Slim App class. See new `ResponseFactoryMiddleware`

### Changed

* Require PHP 8.2 or 8.3. News versions will be supported after a review and test process.
* Migrated all tests to PHPUnit 11
* Update GitHub action and build settings
* Improve DI container integration. Make the DI container a first-class citizen. Require a PSR-11 package.
* Ensure that route attributes are always in the Request. Related to #3280. See new `RoutingArgumentsMiddleware`.
* Unify `CallableResolver` and `AdvancedCallableResolver`. Resolved with the new CallableResolver. Relates to #3073.
- PSR-7 and PSR-15 compliance: Require at least psr/http-message 2.0.
- PSR-11 compliance: Require at least psr/container 2.0.
- PSR-3 compliance: Require at least psr/log 3.0

### Removed

* Psalm
* Old tests for PHP 7
* Router cache file support (File IO was never sufficient. PHP OpCache is much faster)

### Fixed

- Resolving middleware breaks if resolver throws unexpected exception type #3071. Resolved with the new CallableResolver.
- Forward logger to own `ErrorHandlingMiddleware` #2943. See new `ExceptionLoggingMiddleware`.
- Code styles (PSR-12)

## Todo

- Provide [CallbackStream](https://gist.github.com/odan/75c2938c419af2a590675bddeb941a0d#file-callbackstream-php). See #3323
- Provide a ShutdownHandler (using a new ShutdownHandlerInterface)
- Provide App test traits. See #3338

## Files

### Added

- `Slim/Builder/AppBuilder.php`: Introduced to replace `Slim/Factory/AppFactory.php`.
- `Slim/Container/CallableResolver.php`: New implementation of the Callable Resolver.
- `Slim/Container/DefaultDefinitions.php`: Default container definitions.
- `Slim/Handlers/ExceptionHandler.php`: New Exception Handler for better error handling.
- `Slim/Handlers/ExceptionRendererTrait.php`: Common functionality for exception renderers.
- `Slim/Handlers/HtmlExceptionRenderer.php`: HTML-based exception renderer.
- `Slim/Handlers/JsonExceptionRenderer.php`: JSON-based exception renderer.
- `Slim/Handlers/XmlExceptionRenderer.php`: XML-based exception renderer.
- `Slim/Interfaces/ExceptionRendererInterface.php`: New interface for exception renderers.
- `Slim/Logging/StdErrorLogger.php`: Logger that outputs to stderr.
- `Slim/Logging/StdOutLogger.php`: Logger that outputs to stdout.
- `Slim/Middleware/ErrorHandlingMiddleware.php`: Middleware for handling errors.
- `Slim/Middleware/ExceptionHandlingMiddleware.php`: Middleware for handling exceptions.
- `Slim/Middleware/ExceptionLoggingMiddleware.php`: Middleware for logging exceptions.
- `Slim/Middleware/ResponseFactoryMiddleware.php`: Middleware for response creation.
- `Slim/Middleware/UrlGeneratorMiddleware.php`: Middleware for URL generation.
- `Slim/Renderers/JsonRenderer.php`: Renderer for JSON responses.
- `Slim/RequestHandler/MiddlewareRequestHandler.php`: Handles requests through middleware.
- `Slim/RequestHandler/MiddlewareResolver.php`: Resolves middleware for handling requests.
- `Slim/RequestHandler/Runner.php`: Handles the execution flow of requests.
- `Slim/Strategies/RequestResponseNamedArgs.php`: New strategy for named arguments in RequestResponse.
- `Slim/Strategies/RequestResponseTypedArgs.php`: New strategy for typed arguments in RequestResponse.

New files for routing, middleware, and factories, including:

- `Slim/Interfaces/EmitterInterface.php`
- `Slim/Middleware/BasePathMiddleware.php`
- `Slim/Routing/Router.php`, `RouteGroup.php`, `UrlGenerator.php`

### Changed

- `Slim/Interfaces/ErrorHandlerInterface.php` renamed to `Slim/Interfaces/ExceptionHandlerInterface.php`.
- `Slim/Interfaces/RouteParserInterface.php` renamed to `Slim/Interfaces/UrlGeneratorInterface.php`.
- `Slim/Handlers/Strategies/RequestResponse.php` renamed to `Slim/Strategies/RequestResponse.php`.
- `Slim/Handlers/Strategies/RequestResponseArgs.php` renamed to `Slim/Strategies/RequestResponseArgs.php`.
- `Slim/Error/Renderers/PlainTextErrorRenderer.php` renamed to `Slim/Handlers/PlainTextExceptionRenderer.php`.

Various exceptions and middleware were modified, including but not limited to:

- `Slim/Exception/HttpBadRequestException.php`
- `Slim/Middleware/BodyParsingMiddleware.php`
- `Slim/Routing/RouteContext.php`

### Removed

- `Slim/CallableResolver.php`
- `Slim/Handlers/ErrorHandler.php`
- `Slim/Factory/AppFactory.php` and related `Psr17` factories.
- `Slim/Interfaces/AdvancedCallableResolverInterface.php`
- `Slim/Interfaces/RouteCollectorInterface.php`, 
- `RouteCollectorProxyInterface.php`, 
- `RouteGroupInterface.php`, and other route-related interfaces.
- `Slim/Routing/Dispatcher.php`, `FastRouteDispatcher.php`, `Route.php`, and related routing classes.

