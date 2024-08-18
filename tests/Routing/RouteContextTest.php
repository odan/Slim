<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;
use Slim\Routing\UrlGenerator;

class RouteContextTest extends TestCase
{
    public function testFromRequestCreatesInstanceWithValidAttributes(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);
        $basePath = '/base-path';

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults)
            ->withAttribute(RouteContext::BASE_PATH, $basePath);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertInstanceOf(RouteContext::class, $routeContext);
        $this->assertSame($urlGenerator, $routeContext->getUrlGenerator());
        $this->assertSame($routingResults, $routeContext->getRoutingResults());
        $this->assertSame($basePath, $routeContext->getBasePath());
    }

    public function testFromRequestThrowsExceptionIfUrlGeneratorIsMissing(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot create RouteContext before routing has been completed. Add UrlGeneratorMiddleware to fix this.'
        );

        RouteContext::fromRequest($request);
    }

    public function testFromRequestThrowsExceptionIfRoutingResultsAreMissing(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot create RouteContext before routing has been completed. Add RoutingMiddleware to fix this.'
        );

        RouteContext::fromRequest($request);
    }

    public function testGetUrlGeneratorReturnsCorrectInstance(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($urlGenerator, $routeContext->getUrlGenerator());
    }

    public function testGetRoutingResultsReturnsCorrectInstance(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($routingResults, $routeContext->getRoutingResults());
    }

    public function testGetBasePathReturnsCorrectValue(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);
        $basePath = '/base-path';

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults)
            ->withAttribute(RouteContext::BASE_PATH, $basePath);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($basePath, $routeContext->getBasePath());
    }

    public function testGetBasePathReturnsNullIfNotSet(): void
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $urlGenerator = $app->getContainer()->get(UrlGenerator::class);

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::URL_GENERATOR, $urlGenerator)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertNull($routeContext->getBasePath());
    }
}
