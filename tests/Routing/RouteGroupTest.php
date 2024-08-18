<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Slim\Builder\AppBuilder;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

class RouteGroupTest extends TestCase
{
    public function testConstructorInitializesPropertiesCorrectly(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $prefix = '/test';
        $routeGroup = new RouteGroup($prefix, $callback, $router);

        $this->assertSame('/test', $routeGroup->getPrefix());
        $this->assertSame($callback, $routeGroup->getRouteGroup() === null ? $callback : null);
        $this->assertSame($router, $routeGroup->getRouteGroup() === null ? $router : null);
        $this->assertNull($routeGroup->getRouteGroup());
    }

    public function testConstructorWithParentGroup(): void
    {
        $router = $this->createRouter();
        $parentGroupCallback = function () {
        };
        $childGroupCallback = function () {
        };
        $parentGroup = new RouteGroup('/parent', $parentGroupCallback, $router);
        $childGroup = new RouteGroup('/child', $childGroupCallback, $router, $parentGroup);

        $this->assertSame('/child', $childGroup->getPrefix());
        $this->assertSame($parentGroup, $childGroup->getRouteGroup());
    }

    public function testInvokeExecutesCallback(): void
    {
        $router = $this->createRouter();
        $called = false;
        $callback = function () use (&$called) {
            $called = true;
        };
        $routeGroup = new RouteGroup('/test', $callback, $router);

        $routeGroup();
        $this->assertTrue($called);
    }

    public function testMapCreatesAndRegistersRoute(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router);

        $route = $routeGroup->map(['GET'], '/foo', 'handler');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/foo', $route->getPattern());
    }

    public function testGroupCreatesAndRegistersNestedRouteGroup(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router);

        $nestedGroup = $routeGroup->group('/nested', function () {
        });

        $this->assertInstanceOf(RouteGroup::class, $nestedGroup);
        $this->assertSame('/test/nested', $nestedGroup->getPrefix());
        $this->assertSame($routeGroup, $nestedGroup->getRouteGroup());
    }

    private function createRouter(): Router
    {
        $app = (new AppBuilder())->build();

        return $app->getContainer()->get(Router::class);
    }
}
