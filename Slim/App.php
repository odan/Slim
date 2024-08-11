<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\MiddlewareCollectionInterface;
use Slim\Interfaces\RouteCollectionInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\MiddlewareAwareTrait;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectionTrait;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

/**
 * App
 *
 * The main application class for Slim framework, responsible for routing, middleware handling, and
 * running the application. It provides methods for defining routes, adding middleware, and managing
 * the application's lifecycle, including handling HTTP requests and emitting responses.
 *
 * @template TContainerInterface of (ContainerInterface|null)
 *
 * @api
 */
final class App implements RouteCollectionInterface, MiddlewareCollectionInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;

    /**
     * Current version.
     *
     * @var string
     */
    public const VERSION = '5.0.0-alpha';

    private ContainerInterface $container;

    private ServerRequestCreatorInterface $serverRequestCreator;

    private RequestHandlerInterface $requestHandler;

    private Router $router;

    private EmitterInterface $emitter;

    public function __construct(
        ContainerInterface $container,
        ServerRequestCreatorInterface $serverRequestCreator,
        RequestHandlerInterface $requestHandler,
        Router $router,
        EmitterInterface $emitter
    ) {
        $this->container = $container;
        $this->serverRequestCreator = $serverRequestCreator;
        $this->requestHandler = $requestHandler;
        $this->router = $router;
        $this->emitter = $emitter;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function map(array $methods, string $pattern, callable|string $handler): Route
    {
        return $this->router->map($methods, $pattern, $handler);
    }

    public function group(string $pattern, callable $handler): RouteGroup
    {
        return $this->router->group($pattern, $handler);
    }

    /**
     * Get the routing base path
     */
    public function getBasePath(): string
    {
        return $this->router->getBasePath();
    }

    /**
     * Set the routing base path
     */
    public function setBasePath(string $basePath): self
    {
        $this->router->setBasePath($basePath);

        return $this;
    }

    /**
     * Add a new middleware to the stack.
     */
    public function add(MiddlewareInterface|callable|string|array $middleware): self
    {
        $this->router->addMiddleware($middleware);

        return $this;
    }

    /**
     * Add a new middleware to the stack.
     */
    public function addMiddleware(MiddlewareInterface|callable|string|array $middleware): self
    {
        $this->router->addMiddleware($middleware);

        return $this;
    }

    /**
     * Run application.
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $request = $this->serverRequestCreator->createServerRequestFromGlobals();
        }

        $response = $this->handle($request);

        $this->emitter->emit($response);
    }

    /**
     * Handle a request.
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $this->router->getMiddlewareStack());

        return $this->requestHandler->handle($request);
    }
}
