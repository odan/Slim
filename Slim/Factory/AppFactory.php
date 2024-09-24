<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Routing\Router;

final class AppFactory
{
    public static function createFromContainer(ContainerInterface $container): App
    {
        $serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);
        $requestHandler = $container->get(RequestHandlerInterface::class);
        $router = $container->get(Router::class);
        $emitter = $container->get(EmitterInterface::class);

        return new App($container, $serverRequestCreator, $requestHandler, $router, $emitter);
    }
}
