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
use Slim\Formatting\ContentNegotiator;
use Slim\Formatting\HtmlMediaTypeFormatter;
use Slim\Formatting\JsonMediaTypeFormatter;
use Slim\Formatting\PlainTextMediaTypeFormatter;
use Slim\Formatting\XmlMediaTypeFormatter;
use Slim\Handlers\ExceptionHandler;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\ContentNegotiatorInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Logging\StdLogger;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
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
            ExceptionHandlingMiddleware::class => function (ContainerInterface $container) {
                // Default exception handler
                $exceptionHandler = $container->get(ExceptionHandler::class);

                // Settings
                $displayErrorDetails = false;
                if ($container->has('settings')) {
                    $displayErrorDetails = (bool)($container->get('settings')['display_error_details'] ?? false);
                }

                $exceptionHandler->setDisplayErrorDetails($displayErrorDetails);

                return new ExceptionHandlingMiddleware($exceptionHandler);
            },
            ContentNegotiatorInterface::class => function (ContainerInterface $container) {
                $negotiator = $container->get(ContentNegotiator::class);

                $negotiator
                    ->clearHandlers()
                    ->setHandler('application/json', JsonMediaTypeFormatter::class)
                    ->setHandler('text/html', HtmlMediaTypeFormatter::class)
                    ->setHandler('application/xhtml+xml', HtmlMediaTypeFormatter::class)
                    ->setHandler('application/xml', XmlMediaTypeFormatter::class)
                    ->setHandler('text/plain', PlainTextMediaTypeFormatter::class);

                return $negotiator;
            },
            BodyParsingMiddleware::class => function (ContainerInterface $container) {
                $negotiator = $container->get(ContentNegotiatorInterface::class);
                $middleware = new BodyParsingMiddleware($negotiator);
                $middleware->registerDefaultBodyParsers();

                return $middleware;
            },
            LoggerInterface::class => function () {
                return new StdLogger();
            },
        ];
    }
}
