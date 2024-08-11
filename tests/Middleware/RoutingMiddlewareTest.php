<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Builder\AppBuilder;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\UrlGeneratorInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Middleware\UrlGeneratorMiddleware;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

final class RoutingMiddlewareTest extends TestCase
{
    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            // routingResults is available
            /** @var RoutingResults $routingResults */
            $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
            $test->assertInstanceOf(RoutingResults::class, $routingResults);

            // route is available
            $route = $routingResults->getRoute();
            $test->assertNotNull($route);

            // routeParser is available
            $urlGenerator = $request->getAttribute(RouteContext::URL_GENERATOR);
            $test->assertNotNull($urlGenerator);
            $test->assertInstanceOf(UrlGeneratorInterface::class, $urlGenerator);

            return $handler->handle($request);
        };

        $app->add(RoutingMiddleware::class);
        $app->add(UrlGeneratorMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', 'https://example.com:443/hello/foo');

        $app->get('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testRouteIsNotStoredOnMethodNotAllowed()
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $builder = new AppBuilder();
        $app = $builder->build();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            try {
                return $handler->handle($request);
            } catch (HttpMethodNotAllowedException $exception) {
                $request = $exception->getRequest();

                // routingResults is available
                /** @var RoutingResults $routingResults */
                $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
                $test->assertInstanceOf(RoutingResults::class, $routingResults);
                $test->assertSame(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());

                // route is not available
                $route = $routingResults->getRoute();
                $test->assertNull($route);

                // routeParser is available
                $urlParser = $request->getAttribute(RouteContext::URL_GENERATOR);
                $test->assertNotNull($urlParser);
                $test->assertInstanceOf(UrlGeneratorInterface::class, $urlParser);

                // Re-throw to keep the behavior consistent
                throw $exception;
            }
        };

        $app->add(RoutingMiddleware::class);
        $app->add(UrlGeneratorMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        $app->post('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/hello/foo');

        $app->handle($request);
    }

    public function testRouteIsNotStoredOnNotFound()
    {
        $this->expectException(HttpNotFoundException::class);

        $builder = new AppBuilder();
        $app = $builder->build();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            try {
                return $handler->handle($request);
            } catch (HttpNotFoundException $exception) {
                $request = $exception->getRequest();

                // routingResults is available
                /** @var RoutingResults $routingResults */
                $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
                $test->assertInstanceOf(RoutingResults::class, $routingResults);
                $test->assertSame(Dispatcher::NOT_FOUND, $routingResults->getRouteStatus());

                // route is not available
                $route = $routingResults->getRoute();
                $test->assertNull($route);

                // routeParser is available
                $urlGenerator = $request->getAttribute(RouteContext::URL_GENERATOR);
                $test->assertNotNull($urlGenerator);
                $test->assertInstanceOf(UrlGeneratorInterface::class, $urlGenerator);

                // Re-throw to keep the behavior consistent
                throw $exception;
            }
        };

        $app->add(RoutingMiddleware::class);
        $app->add(UrlGeneratorMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        // No route is defined for '/hello/foo'

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/hello/foo');

        $app->handle($request);
    }
}
