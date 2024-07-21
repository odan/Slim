<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\CallableResolver;
use Slim\Tests\Mocks\CallableTester;
use Slim\Tests\Mocks\InvokableTester;
use Slim\Tests\Mocks\MiddlewareTester;
use Slim\Tests\Mocks\RequestHandlerTester;

class CallableResolverTest extends TestCase
{
    private ObjectProphecy $containerProphecy;

    public static function setUpBeforeClass(): void
    {
        function testAdvancedCallable()
        {
            return true;
        }
    }

    public function setUp(): void
    {
        CallableTester::$CalledCount = 0;
        InvokableTester::$CalledCount = 0;
        RequestHandlerTester::$CalledCount = 0;

        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->containerProphecy->has(Argument::type('string'))->willReturn(false);
    }

    public function testClosure(): void
    {
        $test = function () {
            return true;
        };
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertTrue($callable());
        $this->assertTrue($callableRoute());
        $this->assertTrue($callableMiddleware());
    }

    public function testClosureContainer(): void
    {
        $this->containerProphecy->has('ultimateAnswer')->willReturn(true);
        $this->containerProphecy->get('ultimateAnswer')->willReturn(42);

        $that = $this;
        $test = function () use ($that) {
            $that->assertInstanceOf(ContainerInterface::class, $this);

            /** @var ContainerInterface $this */
            return $this->get('ultimateAnswer');
        };

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertSame(42, $callable());
        $this->assertSame(42, $callableRoute());
        $this->assertSame(42, $callableMiddleware());
    }

    public function testFunctionName(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__ . '\testAdvancedCallable');
        $callableRoute = $resolver->resolveRoute(__NAMESPACE__ . '\testAdvancedCallable');
        $callableMiddleware = $resolver->resolveMiddleware(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertTrue($callable());
        $this->assertTrue($callableRoute());
        $this->assertTrue($callableMiddleware());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTester();
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callableRoute = $resolver->resolveRoute([$obj, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([$obj, 'toCall']);

        $callable();
        $this->assertSame(1, CallableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTester::$CalledCount);
    }

    public function testSlimCallable(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTester:toCall');
        $callableRoute = $resolver->resolveRoute('Slim\Tests\Mocks\CallableTester:toCall');
        $callableMiddleware = $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTester:toCall');

        $callable();
        $this->assertSame(1, CallableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTester::$CalledCount);
    }

    public function testSlimCallableAsArray(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([CallableTester::class, 'toCall']);
        $callableRoute = $resolver->resolveRoute([CallableTester::class, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([CallableTester::class, 'toCall']);

        $callable();
        $this->assertSame(1, CallableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTester::$CalledCount);
    }

    public function testSlimCallableContainer(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame($container, CallableTester::$CalledContainer);

        CallableTester::$CalledContainer = null;
        $resolver->resolveRoute('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame($container, CallableTester::$CalledContainer);

        CallableTester::$CalledContainer = null;
        $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame($container, CallableTester::$CalledContainer);
    }

    public function testSlimCallableAsArrayContainer(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve([CallableTester::class, 'toCall']);
        $this->assertSame($container, CallableTester::$CalledContainer);

        CallableTester::$CalledContainer = null;
        $resolver->resolveRoute([CallableTester::class, 'toCall']);
        $this->assertSame($container, CallableTester::$CalledContainer);

        CallableTester::$CalledContainer = null;
        $resolver->resolveMiddleware([CallableTester::class, 'toCall']);
        $this->assertSame($container, CallableTester::$CalledContainer);
    }

    public function testContainer(): void
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();

        $resolver = new CallableResolver($container);
        $callable = $resolver->resolve('callable_service:toCall');
        $callableRoute = $resolver->resolveRoute('callable_service:toCall');
        $callableMiddleware = $resolver->resolveMiddleware('callable_service:toCall');

        $callable();
        $this->assertSame(1, CallableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTester::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer(): void
    {
        $this->containerProphecy->has('an_invokable')->willReturn(true);
        $this->containerProphecy->get('an_invokable')->willReturn(new InvokableTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();

        $resolver = new CallableResolver($container);
        $callable = $resolver->resolve('an_invokable');
        $callableRoute = $resolver->resolveRoute('an_invokable');
        $callableMiddleware = $resolver->resolveMiddleware('an_invokable');

        $callable();
        $this->assertSame(1, InvokableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, InvokableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, InvokableTester::$CalledCount);
    }

    public function testResolutionToAnInvokableClass(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(\Slim\Tests\Mocks\InvokableTester::class);
        $callableRoute = $resolver->resolveRoute(\Slim\Tests\Mocks\InvokableTester::class);
        $callableMiddleware = $resolver->resolveMiddleware(\Slim\Tests\Mocks\InvokableTester::class);

        $callable();
        $this->assertSame(1, InvokableTester::$CalledCount);

        $callableRoute();
        $this->assertSame(2, InvokableTester::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, InvokableTester::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTester is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve(RequestHandlerTester::class);
    }

    public function testRouteResolutionToAPsrRequestHandlerClass(): void
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute(RequestHandlerTester::class);
        $callableRoute($request);
        $this->assertSame(1, RequestHandlerTester::$CalledCount);
    }

    public function testMiddlewareResolutionToAPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTester is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware(RequestHandlerTester::class);
    }

    public function testObjPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTester();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjPsrRequestHandlerClass(): void
    {
        $obj = new RequestHandlerTester();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute($obj);
        $callableRoute($request);
        $this->assertSame(1, RequestHandlerTester::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTester();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware($obj);
    }

    public function testObjPsrRequestHandlerClassInContainer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a_requesthandler is not resolvable');

        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('a_requesthandler');
    }

    public function testRouteObjPsrRequestHandlerClassInContainer(): void
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver($container);
        $callable = $resolver->resolveRoute('a_requesthandler');
        $callable($request);

        $this->assertSame(1, RequestHandlerTester::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClassInContainer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a_requesthandler is not resolvable');

        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTester::class . ':custom');
        $callableRoute = $resolver->resolveRoute(RequestHandlerTester::class . ':custom');
        $callableMiddleware = $resolver->resolveMiddleware(RequestHandlerTester::class . ':custom');

        $this->assertIsArray($callable);
        $this->assertInstanceOf(RequestHandlerTester::class, $callable[0]);
        $this->assertSame('custom', $callable[1]);

        $this->assertIsArray($callableRoute);
        $this->assertInstanceOf(RequestHandlerTester::class, $callableRoute[0]);
        $this->assertSame('custom', $callableRoute[1]);

        $this->assertIsArray($callableMiddleware);
        $this->assertInstanceOf(RequestHandlerTester::class, $callableMiddleware[0]);
        $this->assertSame('custom', $callableMiddleware[1]);
    }

    public function testObjMiddlewareClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTester();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjMiddlewareClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTester();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveRoute($obj);
    }

    public function testMiddlewareObjMiddlewareClass(): void
    {
        $obj = new MiddlewareTester();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRouteMiddleware = $resolver->resolveMiddleware($obj);
        $callableRouteMiddleware($request, $this->createMock(RequestHandlerInterface::class));
        $this->assertSame(1, MiddlewareTester::$CalledCount);
    }

    public function testNotObjectInContainerThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service container entry is not an object');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn('NOT AN OBJECT');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('callable_service');
    }

    public function testMethodNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('callable_service:notFound');
    }

    public function testRouteMethodNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('callable_service:notFound');
    }

    public function testMiddlewareMethodNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTester());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('callable_service:notFound');
    }

    public function testFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('notFound');
    }

    public function testRouteFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('notFound');
    }

    public function testMiddlewareFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('notFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('Unknown:notFound');
    }

    public function testRouteClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('Unknown:notFound');
    }

    public function testMiddlewareClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve(['Unknown', 'notFound']);
    }

    public function testRouteCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute(['Unknown', 'notFound']);
    }

    public function testMiddlewareCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware(['Unknown', 'notFound']);
    }
}
