<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Container\ContainerResolver;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Tests\Mocks\CallableTester;
use Slim\Tests\Mocks\InvokableTester;
use Slim\Tests\Mocks\MiddlewareTester;
use Slim\Tests\Mocks\RequestHandlerTester;
use Slim\Tests\Traits\AppTestTrait;
use TypeError;

final class ContainerResolverTest extends TestCase
{
    use AppTestTrait;

    protected function createResolver(): ContainerResolver
    {
        $container = $this->createContainer();

        return new ContainerResolver($container);
    }

    public function testClosure(): void
    {
        $test = function () {
            return true;
        };
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable($test);

        $this->assertTrue($callable());
    }

    public function testClosureContainer(): void
    {
        $this->setUpApp([
            'ultimateAnswer' => fn () => 42,
        ]);

        $that = $this;
        $test = function () use ($that) {
            $that->assertInstanceOf(ContainerInterface::class, $this);
            $that->assertSame($that->container, $this);

            /** @var ContainerInterface $this */
            return $this->get('ultimateAnswer');
        };

        $resolver = $this->container->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveRoute($test);

        $this->assertSame(42, $callable());
    }

    public function testFunctionName(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertTrue($callable());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTester();
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable([$obj, 'toCall']);
        $this->assertSame(true, $callable());
    }

    public function testSlimCallable(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame(true, $callable());
    }

    public function testPhpCallable(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable('Slim\Tests\Mocks\CallableTester::toCall');
        $this->assertSame(true, $callable());
    }

    public function testSlimCallableAsArray(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable([CallableTester::class, 'toCall']);
        $this->assertSame(true, $callable());
    }

    public function testContainer(): void
    {
        $container = $this->createContainer();
        $container->set('callable_service', function () {
            return new CallableTester();
        });

        $resolver = new ContainerResolver($container);
        $callable = $resolver->resolveCallable('callable_service:toCall');
        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClassInContainer(): void
    {
        $container = $this->createContainer();
        $container->set('an_invokable', function () {
            return new InvokableTester();
        });

        $resolver = new ContainerResolver($container);
        $callable = $resolver->resolveCallable('an_invokable');

        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClass(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable(InvokableTester::class);
        $this->assertSame(true, $callable());
    }

    public function testResolutionToRequestHandler(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "Slim\Tests\Mocks\RequestHandlerTester" is not a callable');

        $resolver = $this->createResolver();
        $resolver->resolveCallable(RequestHandlerTester::class);
    }

    public function testObjRequestHandlerInContainer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "a_requesthandler" is not a callable');

        $container = $this->createContainer();
        $container->set('a_requesthandler', function ($container) {
            return new RequestHandlerTester($container->get(ResponseFactoryInterface::class));
        });

        $resolver = new ContainerResolver($container);
        $resolver->resolveCallable('a_requesthandler');
    }

    public function __testRouteObjPsrRequestHandlerClassInContainer(): void
    {
        $container = $this->createContainer();
        $container->set('a_requesthandler', function () {
            return new RequestHandlerTester();
        });

        $request = $this->createServerRequest('GET', '/');
        $resolver = new ContainerResolver($container);
        // $callable = $resolver->resolveRoute('a_requesthandler');
        $callable = $resolver->resolveCallable('a_requesthandler');

        $this->assertSame('CALLED', $callable($request));
    }

    public function __testMiddlewareObjPsrRequestHandlerClassInContainer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('a_requesthandler is not resolvable');

        $container = $this->createContainer();
        $container->set('a_requesthandler', function () {
            return new RequestHandlerTester();
        });
        $resolver = new ContainerResolver($container);
        $resolver->resolveMiddleware('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod(): void
    {
        $resolver = $this->createResolver();
        $callable = $resolver->resolveCallable(RequestHandlerTester::class . ':custom');

        $this->assertIsArray($callable);
        $this->assertInstanceOf(RequestHandlerTester::class, $callable[0]);
        $this->assertSame('custom', $callable[1]);
    }

    public function testObjMiddlewareClass(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type callable|array|string');

        $obj = new MiddlewareTester();
        $resolver = $this->createResolver();
        $resolver->resolveCallable($obj);
    }

    public function testNotObjectInContainerThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "callable_service" is not a callable');

        $container = $this->createContainer();
        $container->set('callable_service', function () {
            return 'NOT AN OBJECT';
        });

        $resolver = new ContainerResolver($container);
        $resolver->resolveCallable('callable_service');
    }

    public function testMethodNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The method "notFound" does not exists');

        $container = $this->createContainer();
        $container->set('callable_service', function () {
            return new CallableTester();
        });
        $resolver = new ContainerResolver($container);
        $resolver->resolveCallable('callable_service:notFound');
    }

    public function testFunctionNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'notFound'");

        $container = $this->createContainer();
        $resolver = new ContainerResolver($container);
        $resolver->resolveCallable('notFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $container = $this->createContainer();
        $resolver = new ContainerResolver($container);
        $resolver->resolveCallable('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $resolver = $this->createResolver();
        $resolver->resolveCallable(['Unknown', 'notFound']);
    }
}

function testAdvancedCallable()
{
    return true;
}
