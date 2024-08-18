<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Builder\AppBuilder;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

class RouteTest extends TestCase
{
    public function testGetHandlerReturnsCorrectHandler(): void
    {
        $methods = ['GET'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        $route = new Route($methods, $pattern, $handler);

        $this->assertSame($handler, $route->getHandler());
    }

    public function testGetMiddlewareStackWithoutGroup(): void
    {
        $methods = ['GET'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        $route = new Route($methods, $pattern, $handler);

        // Adding middleware
        $middleware1 = $this->createMiddleware();
        $middleware2 = $this->createMiddleware();
        $route->add($middleware1)->add($middleware2);

        $middlewareStack = $route->getMiddlewareStack();

        $this->assertCount(2, $middlewareStack);
        $this->assertSame([$middleware1, $middleware2], $middlewareStack);
    }

    public function testGetMiddlewareStackWithGroup(): void
    {
        $methods = ['GET'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        // Creating middlewares
        $middleware1 = $this->createMiddleware();
        $middleware2 = $this->createMiddleware();
        $groupMiddleware = $this->createMiddleware();

        // Create a Router instance
        $router = $this->createRouter();

        // Create a RouteGroup with middleware
        $routeGroup = new RouteGroup('/group', function (RouteGroup $group) use ($groupMiddleware) {
            $group->add($groupMiddleware);
        }, $router);

        $route = new Route($methods, $pattern, $handler, $routeGroup);

        // Adding middleware to route
        $route->add($middleware1)->add($middleware2);

        // Simulate fastroute group collector
        $routeGroup();

        $middlewareStack = $route->getMiddlewareStack();

        // The stack should contain route middlewares followed by group middleware
        $this->assertCount(3, $middlewareStack);
        $this->assertSame([$middleware1, $middleware2, $groupMiddleware], $middlewareStack);
    }

    public function testSetNameAndGetName(): void
    {
        $methods = ['GET'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        $route = new Route($methods, $pattern, $handler);

        $route->setName('test-route');

        $this->assertSame('test-route', $route->getName());
    }

    public function testGetPatternReturnsCorrectPattern(): void
    {
        $methods = ['GET'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        $route = new Route($methods, $pattern, $handler);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testGetMethodsReturnsCorrectMethods(): void
    {
        $methods = ['GET', 'POST'];
        $pattern = '/test';
        $handler = function () {
            return 'handler';
        };

        $route = new Route($methods, $pattern, $handler);

        $this->assertSame($methods, $route->getMethods());
    }

    private function createMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    private function createRouter(): Router
    {
        $app = (new AppBuilder())->build();

        return $app->getContainer()->get(Router::class);
    }
}
