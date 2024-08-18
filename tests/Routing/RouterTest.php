<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use FastRoute\RouteCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Builder\AppBuilder;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

class RouterTest extends TestCase
{
    #[DataProvider('httpMethodProvider')]
    public function testHttpMethods(string $methodName, string $path, callable $handler, array $expectedMethods): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        // Define a route using the HTTP method from the data provider
        $route = $router->{$methodName}($path, $handler);

        // Verify the route is mapped correctly
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($handler, $route->getHandler());
        $this->assertSame($path, $route->getPattern());

        // Verify that all expected methods are present in the route's methods
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $route->getMethods(),
                "Method $expectedMethod not found in route methods"
            );
        }
    }

    public static function httpMethodProvider(): array
    {
        return [
            [
                'any',
                '/any',
                function () {
                    return 'any_handler';
                },
                ['*'],
            ],
            [
                'delete',
                '/delete',
                function () {
                    return 'delete_handler';
                },
                ['DELETE'],
            ],
            [
                'get',
                '/get',
                function () {
                    return 'get_handler';
                },
                ['GET'],
            ],
            [
                'head',
                '/head',
                function () {
                    return 'head_handler';
                },
                ['HEAD'],
            ],
            [
                'options',
                '/options',
                function () {
                    return 'options_handler';
                },
                ['OPTIONS'],
            ],
            [
                'patch',
                '/patch',
                function () {
                    return 'patch_handler';
                },
                ['PATCH'],
            ],
            [
                'post',
                '/post',
                function () {
                    return 'post_handler';
                },
                ['POST'],
            ],
            [
                'put',
                '/put',
                function () {
                    return 'put_handler';
                },
                ['PUT'],
            ],
        ];
    }

    public function testMapCreatesRoute(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $methods = ['GET'];
        $path = '/test-route';
        $handler = function () {
            return 'Test Handler';
        };

        $route = $router->map($methods, $path, $handler);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($methods, $route->getMethods());
        $this->assertSame($router->getBasePath() . $path, $route->getPattern());
        $this->assertSame($handler, $route->getHandler());
    }

    public function testGroupCreatesRouteGroup(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $pattern = '/group';
        $handler = function (RouteGroup $group) {
            $group->map(['GET'], '/foo', 'foo_handler');
        };

        $routeGroup = $router->group($pattern, $handler);

        $this->assertInstanceOf(RouteGroup::class, $routeGroup);
        $this->assertSame($router->getBasePath() . $pattern, $routeGroup->getPrefix());
    }

    public function testGetRouteCollectorReturnsCollector(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $collector = $router->getRouteCollector();
        $this->assertInstanceOf(RouteCollector::class, $collector);
    }

    public function testSetAndGetBasePath(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $basePath = '/base-path';
        $router->setBasePath($basePath);

        $this->assertSame($basePath, $router->getBasePath());
    }

    public function testMapWithBasePath(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $basePath = '/base-path';
        $router->setBasePath($basePath);

        $methods = ['GET'];
        $path = '/test-route';
        $handler = function () {
            return 'Test Handler';
        };

        $route = $router->map($methods, $path, $handler);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($methods, $route->getMethods());
        $this->assertSame($basePath . $path, $route->getPattern());
        $this->assertSame($handler, $route->getHandler());
    }
}
