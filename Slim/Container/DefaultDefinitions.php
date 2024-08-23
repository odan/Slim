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
use Slim\Emitter\ResponseEmitter;
use Slim\Formatting\HtmlErrorFormatter;
use Slim\Formatting\JsonErrorFormatter;
use Slim\Formatting\MediaTypeDetector;
use Slim\Formatting\PlainTextErrorFormatter;
use Slim\Formatting\XmlErrorFormatter;
use Slim\Handlers\ExceptionHandler;
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
use Slim\Strategies\RequestResponse;

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
                'display_error_details' => false,
                'log_error_details' => false,
            ],
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
                $displayErrorDetails = false;
                if ($container->has('settings')) {
                    $displayErrorDetails = (bool)($container->get('settings')['display_error_details'] ?? false);
                }

                $exceptionHandler->setDisplayErrorDetails($displayErrorDetails);
                $exceptionHandler->setDefaultMediaType('text/html');

                return $exceptionHandler
                    ->clearHandlers()
                    ->setHandler('application/json', JsonErrorFormatter::class)
                    ->setHandler('application/problem+json', JsonErrorFormatter::class)
                    ->setHandler('text/html', HtmlErrorFormatter::class)
                    ->setHandler('application/xhtml+xml', HtmlErrorFormatter::class)
                    ->setHandler('application/xml', XmlErrorFormatter::class)
                    ->setHandler('text/xml', XmlErrorFormatter::class)
                    ->setHandler('text/plain', PlainTextErrorFormatter::class);
            },

            ExceptionLoggingMiddleware::class => function (ContainerInterface $container) {
                // Default logger
                $logger = $container->get(LoggerInterface::class);
                $middleware = new ExceptionLoggingMiddleware($logger);

                // Read settings
                $logErrorDetails = false;
                if ($container->has('settings')) {
                    $logErrorDetails = (bool)($container->get('settings')['log_error_details'] ?? false);
                }

                return $middleware->setLogErrorDetails($logErrorDetails);
            },
            BodyParsingMiddleware::class => function (ContainerInterface $container) {
                $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                return $middleware
                    ->setDefaultMediaType('text/html')
                    ->registerDefaultBodyParsers();
            },
            LoggerInterface::class => function () {
                return new StdLogger();
            },
        ];
    }
}
