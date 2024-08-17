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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Slim\Emitter\ResponseEmitter;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\HttpSoftPsr17Factory;
use Slim\Factory\Psr17\LaminasDiactorosPsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\SlimDecoratedPsr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
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
use Slim\Logging\StdErrorLogger;
use Slim\Logging\StdOutLogger;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
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
 * The provided definitions cover:
 * - Configuration settings, such as error display.
 * - Core components like the `App` instance, container resolver, and request/response handling.
 * - Factories for PSR-17 implementations including `ServerRequestFactoryInterface`,
 *   `ResponseFactoryInterface`, `StreamFactoryInterface`, and others.
 * - Middleware such as `ExceptionHandlingMiddleware` and `ExceptionLoggingMiddleware`.
 * - Logging services.
 *
 * This class ensures that the Slim application can be properly instantiated with the necessary
 * components and services. It also provides the flexibility in selecting the
 * appropriate PSR-17 implementations based on the available libraries.
 */
final class DefaultDefinitions
{
    public function __invoke(): array
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
            // DI container resolver
            ContainerResolverInterface::class => function (ContainerInterface $container) {
                return $container->get(ContainerResolver::class);
            },
            // Request and response
            RequestHandlerInterface::class => function (ContainerInterface $container) {
                return $container->get(MiddlewareRequestHandler::class);
            },
            ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                $requestFactoryClasses = [
                    \Slim\Http\Factory\DecoratedServerRequestFactory::class => SlimDecoratedPsr17Factory::class,
                    \Slim\Psr7\Factory\ServerRequestFactory::class => SlimPsr17Factory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class => NyholmPsr17Factory::class,
                    \Laminas\Diactoros\ResponseFactory::class => LaminasDiactorosPsr17Factory::class,
                    \GuzzleHttp\Psr7\ServerRequest::class => GuzzlePsr17Factory::class,
                    \HttpSoft\Message\ResponseFactory::class => HttpSoftPsr17Factory::class,
                ];

                foreach ($requestFactoryClasses as $requestFactoryClass => $factoryClass) {
                    if (class_exists($requestFactoryClass)) {
                        return $container->get($factoryClass);
                    }
                }

                throw new RuntimeException('Could not instantiate a server request factory.');
            },
            ServerRequestCreatorInterface::class => function (ContainerInterface $container) {
                return $container->get(ServerRequestFactoryInterface::class);
            },
            ResponseFactoryInterface::class => function (ContainerInterface $container) {
                $responseFactory = null;
                $decoratedResponseFactory = \Slim\Http\Factory\DecoratedResponseFactory::class;
                $isDecorated = class_exists($decoratedResponseFactory);

                $responseFactoryClasses = [
                    \Slim\Psr7\Factory\ResponseFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\ResponseFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\ResponseFactory::class,
                ];

                foreach ($responseFactoryClasses as $responseFactoryClass) {
                    if (class_exists($responseFactoryClass)) {
                        $responseFactory = $container->get($responseFactoryClass);
                        break;
                    }
                }

                if ($isDecorated && $responseFactory instanceof ResponseFactoryInterface) {
                    /* @var StreamFactoryInterface $streamFactory */
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $responseFactory = new $decoratedResponseFactory($responseFactory, $streamFactory);
                }

                return $responseFactory ?? throw new RuntimeException(
                    'Could not detect any PSR-17 ResponseFactory implementations. ' .
                    'Please install a supported implementation. ' .
                    'See https://github.com/slimphp/Slim/blob/5.x/README.md for a list of supported implementations.'
                );
            },
            StreamFactoryInterface::class => function (ContainerInterface $container) {
                $streamFactoryClasses = [
                    \Slim\Psr7\Factory\StreamFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\StreamFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\StreamFactory::class,
                ];

                foreach ($streamFactoryClasses as $responseFactoryClass) {
                    if (class_exists($responseFactoryClass)) {
                        return $container->get($responseFactoryClass);
                    }
                }

                throw new RuntimeException('Could not instantiate a stream factory.');
            },
            UriFactoryInterface::class => function (ContainerInterface $container) {
                $uriFactory = null;
                $decoratedUriFactory = \Slim\Http\Factory\DecoratedUriFactory::class;
                $isDecorated = class_exists($decoratedUriFactory);

                $uriFactoryClasses = [
                    \Slim\Psr7\Factory\UriFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\UriFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\UriFactory::class,
                ];

                foreach ($uriFactoryClasses as $uriFactoryClass) {
                    if (class_exists($uriFactoryClass)) {
                        $uriFactory = $container->get($uriFactoryClass);
                        break;
                    }
                }

                if ($isDecorated && $uriFactory instanceof UriFactoryInterface) {
                    $uriFactory = new $decoratedUriFactory($uriFactory);
                }

                if ($uriFactory) {
                    return $uriFactory;
                }

                throw new RuntimeException('Could not instantiate a URI factory.');
            },
            UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
                $uploadFileFactoryClasses = [
                    \Slim\Psr7\Factory\UploadedFileFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\UploadedFileFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\UriFactory::class,
                ];

                foreach ($uploadFileFactoryClasses as $uploadFileFactoryClass) {
                    if (class_exists($uploadFileFactoryClass)) {
                        return $container->get($uploadFileFactoryClass);
                    }
                }

                throw new RuntimeException('Could not instantiate a upload file factory.');
            },
            EmitterInterface::class => function () {
                return new ResponseEmitter();
            },
            // Routing
            Router::class => function () {
                return new Router(new RouteCollector(new Std(), new GroupCountBased()));
            },
            RequestHandlerInvocationStrategyInterface::class => function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },
            // Exception and error handling
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
                    ->clearFormatters()
                    ->setHandler('application/json', JsonMediaTypeFormatter::class)
                    ->setHandler('text/html', HtmlMediaTypeFormatter::class)
                    ->setHandler('application/xhtml+xml', HtmlMediaTypeFormatter::class)
                    ->setHandler('application/xml', XmlMediaTypeFormatter::class)
                    ->setHandler('text/plain', PlainTextMediaTypeFormatter::class);

                return $negotiator;
            },
            // Middleware
            ExceptionLoggingMiddleware::class => function () {
                return new ExceptionLoggingMiddleware(new StdErrorLogger());
            },
            BodyParsingMiddleware::class => function (ContainerInterface $container) {
                $negotiator = $container->get(ContentNegotiatorInterface::class);
                $middleware = new BodyParsingMiddleware($negotiator);
                $middleware->registerDefaultBodyParsers();

                return $middleware;
            },
            // Logging
            LoggerInterface::class => function () {
                return new StdOutLogger();
            },
        ];
    }
}
