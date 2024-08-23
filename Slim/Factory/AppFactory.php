<?php

declare(strict_types=1);

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Slim\Builder\AppBuilder;
use Slim\Enums\MiddlewareOrder;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ErrorHandlingMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\Router;

/**
 * @deprecated use the AppBuilder class instead
 */
final class AppFactory
{
    /**
     * @return App
     */
    public static function create(): App
    {
        $builder = new AppBuilder();

        // Use LIFO like in Slim 4
        $builder->setMiddlewareOrder(MiddlewareOrder::LIFO);

        $builder->setDefinitions([
            App::class => function (ContainerInterface $container) {
                $serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);
                $requestHandler = $container->get(RequestHandlerInterface::class);
                $router = $container->get(Router::class);
                $emitter = $container->get(EmitterInterface::class);

                return new class ($container, $serverRequestCreator, $requestHandler, $router, $emitter) extends App {
                    public function addRoutingMiddleware(): void
                    {
                        $this->add(EndpointMiddleware::class);
                        $this->add(RoutingMiddleware::class);
                    }

                    public function addErrorMiddleware(
                        bool $displayErrorDetails = false,
                        bool $logErrors = false,
                        bool $logErrorDetails = false,
                        ?LoggerInterface $logger = null
                    ): void {
                        if ($displayErrorDetails === true) {
                            throw new RuntimeException(
                                'Displaying error details must be configured in the App settings now.' .
                                'Please use the AppBuilder and enable "display_error_details".'
                            );
                        }
                        if ($logErrorDetails === true && $logger === null) {
                            throw new RuntimeException(
                                'Logging error details without a logger is not supported. ' .
                                'Please use the AppBuilder and enable "log_error_details".'
                            );
                        }

                        $this->add(ExceptionHandlingMiddleware::class);
                        $this->add(ErrorHandlingMiddleware::class);

                        if ($logErrors) {
                            $loggingMiddleware = $logger ? new ExceptionLoggingMiddleware(
                                $logger
                            ) : ExceptionLoggingMiddleware::class;
                            $this->add($loggingMiddleware);
                        }
                    }

                    public function addBodyParsingMiddleware(): void
                    {
                        $this->add(BodyParsingMiddleware::class);
                    }
                };
            },
        ]);

        return $builder->build();
    }
}
