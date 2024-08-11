<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use DI\Container;
use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Container\ContainerResolver;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Interfaces\UrlGeneratorInterface;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteParser;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Strategies\RequestResponseTypedArgs;

final class RoutingMiddlewareTest extends TestCase
{
    protected function getRouteCollector()
    {
        $callableResolver = new ContainerResolver(new Container());
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver, new Container(), new RequestResponseTypedArgs());
        $routeCollector->map(['GET'], '/hello/{name}', null);

        return $routeCollector;
    }

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (ServerRequestInterface $request) use ($responseFactory, $test) {
            // route is available
            $route = $request->getAttribute(RouteContext::ROUTE);
            $test->assertNotNull($route);
            $test->assertSame('foo', $route->getArgument('name'));

            // routeParser is available
            $routeParser = $request->getAttribute(RouteContext::URL_GENERATOR);
            $test->assertNotNull($routeParser);
            $test->assertInstanceOf(UrlGeneratorInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
            $test->assertInstanceOf(RoutingResults::class, $routingResults);

            return $responseFactory->createResponse();
        });

        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $routingMiddleware = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            new Container()
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($routingMiddleware);
        $middlewareDispatcher->handle($request);
    }

    public function testRouteIsNotStoredOnMethodNotAllowed()
    {
        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $routingMiddleware = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'POST');
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @var RequestHandlerInterface $requestHandler */
        $requestHandler = $requestHandlerProphecy->reveal();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($requestHandler, null);
        $middlewareDispatcher->addMiddleware($routingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
            $this->fail('HTTP method should not have been allowed');
        } catch (HttpMethodNotAllowedException $exception) {
            $request = $exception->getRequest();

            // route is not available
            $route = $request->getAttribute(RouteContext::ROUTE);
            $this->assertNull($route);

            // routeParser is available
            $routeParser = $request->getAttribute(RouteContext::URL_GENERATOR);
            $this->assertNotNull($routeParser);
            $this->assertInstanceOf(UrlGeneratorInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertSame(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());
        }
    }

    public function testRouteIsNotStoredOnNotFound()
    {
        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $routingMiddleware = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/goodbye', 'GET');
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @var RequestHandlerInterface $requestHandler */
        $requestHandler = $requestHandlerProphecy->reveal();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($requestHandler, null);
        $middlewareDispatcher->addMiddleware($routingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
            $this->fail('HTTP route should not have been found');
        } catch (HttpNotFoundException $exception) {
            $request = $exception->getRequest();

            // route is not available
            $route = $request->getAttribute(RouteContext::ROUTE);
            $this->assertNull($route);

            // routeParser is available
            $routeParser = $request->getAttribute(RouteContext::URL_GENERATOR);
            $this->assertNotNull($routeParser);
            $this->assertInstanceOf(UrlGeneratorInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertSame(Dispatcher::NOT_FOUND, $routingResults->getRouteStatus());
        }
    }

    public function testPerformRoutingThrowsExceptionOnInvalidRoutingResultsRouteStatus()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An unexpected error occurred while performing routing.');

        // Prophesize the `RoutingResults` instance that would return an invalid route
        // status when the method `getRouteStatus()` gets called.
        $routingResultsProphecy = $this->prophesize(RoutingResults::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $routingResultsProphecy->getRouteStatus()
            ->willReturn(-1)
            ->shouldBeCalledOnce();
        /** @var RoutingResults $routingResults */
        $routingResults = $routingResultsProphecy->reveal();

        // Prophesize the `RouteParserInterface` instance will be created.
        $routeParserProphecy = $this->prophesize(RouteParser::class);
        /** @var UrlGeneratorInterface $routeParser */
        $routeParser = $routeParserProphecy->reveal();

        // Prophesize the `RouteResolverInterface` that would return the `RoutingResults`
        // defined above, when the method `computeRoutingResults()` gets called.
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $routeResolverProphecy->computeRoutingResults(Argument::any(), Argument::any())
            ->willReturn($routingResults)
            ->shouldBeCalled();
        /** @var RouteResolverInterface $routeResolver */
        $routeResolver = $routeResolverProphecy->reveal();

        // Create the server request.
        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        // Create the routing middleware with the `RouteResolverInterface` defined
        // above. Perform the routing, which should throw the RuntimeException.
        $middleware = new RoutingMiddleware($routeResolver, $routeParser);
        /** @noinspection PhpUnhandledExceptionInspection */
        $middleware->performRouting($request);
    }
}
