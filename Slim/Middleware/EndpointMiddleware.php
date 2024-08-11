<?php

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

/**
 * This middleware processes the routing results to determine if a route was found,
 * if the HTTP method is allowed, or if the route was not found. Based on these results,
 * it either executes the found route's handler with its associated middleware stack or
 * throws appropriate exceptions for 404 Not Found or 405 Method Not Allowed.
 */
final class EndpointMiddleware implements MiddlewareInterface
{
    private ContainerResolverInterface $containerResolver;
    private ResponseFactoryInterface $responseFactory;
    private RequestHandlerInterface $requestHandler;
    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    public function __construct(
        ContainerResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInterface $requestHandler,
        RequestHandlerInvocationStrategyInterface $invocationStrategy
    ) {
        $this->containerResolver = $callableResolver;
        $this->responseFactory = $responseFactory;
        $this->requestHandler = $requestHandler;
        $this->invocationStrategy = $invocationStrategy;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /* @var RoutingResults $routingResults */
        $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);

        if (!$routingResults instanceof RoutingResults) {
            throw new RuntimeException(
                'An unexpected error occurred while handling routing results. Routing results are not available.'
            );
        }

        $routeStatus = $routingResults->getRouteStatus();
        if ($routeStatus === RoutingResults::FOUND) {
            return $this->handleFound($request, $routingResults);
        }

        if ($routeStatus === RoutingResults::NOT_FOUND) {
            // 404 Not Found
            throw new HttpNotFoundException($request);
        }

        if ($routeStatus === RoutingResults::METHOD_NOT_ALLOWED) {
            // 405 Method Not Allowed
            $exception = new HttpMethodNotAllowedException($request);
            $exception->setAllowedMethods($routingResults->getAllowedMethods());

            throw $exception;
        }

        throw new RuntimeException('An unexpected error occurred while endpoint handling.');
    }

    private function handleFound(
        ServerRequestInterface $request,
        RoutingResults $routingResults
    ): ResponseInterface {
        $route = $routingResults->getRoute() ?? throw new RuntimeException('Route not found.');
        $vars = $routingResults->getRouteArguments();

        $response = $this->responseFactory->createResponse();

        // Get handler and middlewares
        $actionHandler = $route->getHandler();
        $middlewares = $route->getMiddlewareStack();

        // Endpoint and group specific middleware
        if ($middlewares) {
            $response = $this->invokeMiddlewareStack($request, $response, $middlewares);
        }

        $actionHandler = $this->containerResolver->resolveRoute($actionHandler);

        return call_user_func($this->invocationStrategy, $actionHandler, $request, $response, $vars);
    }

    private function invokeMiddlewareStack(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $middlewares
    ): ResponseInterface {
        // Tunnel the response object through the route/group specific middleware stack
        $middlewares[] = new class ($response) implements MiddlewareInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->response;
            }
        };

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $middlewares);

        return $this->requestHandler->handle($request);
    }
}
