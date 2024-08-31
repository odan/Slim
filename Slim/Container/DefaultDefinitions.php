<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Constants\MediaType;
use Slim\Emitter\ResponseEmitter;
use Slim\Formatting\HtmlErrorFormatter;
use Slim\Formatting\JsonErrorFormatter;
use Slim\Formatting\MediaTypeDetector;
use Slim\Formatting\PlainTextErrorFormatter;
use Slim\Formatting\XmlErrorFormatter;
use Slim\Handlers\ExceptionHandler;
use Slim\Interfaces\ConfigInterface;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Logging\StdLogger;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Router;
use Slim\Routing\Strategies\RequestResponse;
use Slim\Settings\Config;

/**
 * This class provides the default dependency definitions for a Slim application. It implements the
 * `__invoke()` method to return an array of service definitions that are used to set up the Slim
 * framework’s core components, including the application instance, middleware, request and response
 * factories, and other essential services.
 *
 * This class ensures that the Slim application can be properly instantiated with the necessary
 * components and services. It also selects the appropriate PSR-17 implementations based on the available libraries.
 */
final class DefaultDefinitions
{
    public function __invoke(): array
    {
        $definitions = $this->getDefaultDefinitions();

        return array_merge($definitions, call_user_func(new HttpDefinitions()));
    }

    private function getDefaultDefinitions(): array
    {
        return [
            // Configuration
            'settings' => [
                'exception_handler' => [
                    'display_error_details' => false,
                ],
                'exception_logging_middleware' => [
                    'log_error_details' => false,
                ],
            ],
            ConfigInterface::class => function (ContainerInterface $container) {
                return new Config($container->get('settings'));
            },
            // Slim application
            App::class => function (ContainerInterface $container) {
                $serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);
                $requestHandler = $container->get(RequestHandlerInterface::class);
                $router = $container->get(Router::class);
                $emitter = $container->get(EmitterInterface::class);

                return new App($container, $serverRequestCreator, $requestHandler, $router, $emitter);
            },
            ContainerResolverInterface::class => function (ContainerInterface $container) {
                return $container->get(ContainerResolver::class);
            },
            RequestHandlerInterface::class => function (ContainerInterface $container) {
                return $container->get(MiddlewareRequestHandler::class);
            },
            EmitterInterface::class => function () {
                return new ResponseEmitter();
            },
            Router::class => function () {
                return new Router(new RouteCollector(new Std(), new GroupCountBased()));
            },
            RequestHandlerInvocationStrategyInterface::class => function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },
            ExceptionHandlerInterface::class => function (ContainerInterface $container) {
                // Default exception handler
                $exceptionHandler = $container->get(ExceptionHandler::class);

                // Settings
                $displayErrorDetails = (bool)$container->get(ConfigInterface::class)
                    ->get('exception_handler.display_error_details', false);

                $exceptionHandler = $exceptionHandler
                    ->withDisplayErrorDetails($displayErrorDetails)
                    ->withDefaultMediaType(MediaType::TEXT_HTML);

                return $exceptionHandler
                    ->withoutHandlers()
                    ->withHandler(MediaType::APPLICATION_JSON, JsonErrorFormatter::class)
                    ->withHandler(MediaType::TEXT_HTML, HtmlErrorFormatter::class)
                    ->withHandler(MediaType::APPLICATION_XHTML_XML, HtmlErrorFormatter::class)
                    ->withHandler(MediaType::APPLICATION_XML, XmlErrorFormatter::class)
                    ->withHandler(MediaType::TEXT_XML, XmlErrorFormatter::class)
                    ->withHandler(MediaType::TEXT_PLAIN, PlainTextErrorFormatter::class);
            },

            ExceptionLoggingMiddleware::class => function (ContainerInterface $container) {
                // Default logger
                $logger = $container->get(LoggerInterface::class);
                $middleware = new ExceptionLoggingMiddleware($logger);

                // Read settings
                $logErrorDetails = (bool)$container->get(ConfigInterface::class)
                    ->get('exception_logging_middleware.log_error_details', false);

                return $middleware->withLogErrorDetails($logErrorDetails);
            },
            BodyParsingMiddleware::class => function (ContainerInterface $container) {
                $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                return $middleware
                    ->withDefaultMediaType('text/html')
                    ->withDefaultBodyParsers();
            },
            LoggerInterface::class => function () {
                return new StdLogger();
            },
        ];
    }
}
