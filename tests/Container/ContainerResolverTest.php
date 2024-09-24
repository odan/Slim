<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use Closure;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Builder\AppBuilder;
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

    public function testClosure(): void
    {
        $test = function () {
            return true;
        };

        $app = (new AppBuilder())->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $callable = $resolver->resolveCallable($test);

        $this->assertTrue($callable());
    }

    public function testClosureContainer(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'ultimateAnswer' => fn () => 42,
            ]
        );
        $app = $builder->build();
        $container = $app->getContainer();

        $that = $this;
        $test = function () use ($that, $container) {
            $that->assertInstanceOf(ContainerInterface::class, $this);
            $that->assertSame($container, $this);

            /** @var ContainerInterface $this */
            return $this->get('ultimateAnswer');
        };

        $resolver = $container->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveRoute($test);

        $this->assertSame(42, $callable());
    }

    public function testClosureFromCallable(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $container = $app->getContainer();

        $that = $this;
        $class = Closure::fromCallable(
            function () use ($that, $container) {
                $that->assertSame($container, $this);

                return 42;
            }
        );

        $test = [$class, '__invoke'];

        $resolver = $container->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveRoute($test);

        $this->assertSame(42, $callable());
    }

    public function testFunctionName(): void
    {
        $app = (new AppBuilder())->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertTrue($callable());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTester();
        $app = (new AppBuilder())->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable([$obj, 'toCall']);
        $this->assertSame(true, $callable());
    }

    public function testSlimCallable(): void
    {
        $app = (new AppBuilder())->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame(true, $callable());
    }

    public function testSlimCallableAsArray(): void
    {
        $app = (new AppBuilder())->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable([CallableTester::class, 'toCall']);

        $this->assertSame(true, $callable());
    }

    public function testContainer(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'callable_service' => fn () => new CallableTester(),
            ]
        );
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $callable = $resolver->resolveCallable('callable_service:toCall');
        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClassInContainer(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'an_invokable' => fn () => new InvokableTester(),
            ]
        );
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable('an_invokable');

        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClass(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable(InvokableTester::class);
        $this->assertSame(true, $callable());
    }

    public function testResolutionToRequestHandler(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "Slim\Tests\Mocks\RequestHandlerTester" is not a callable');

        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $resolver->resolveCallable(RequestHandlerTester::class);
    }

    public function testObjRequestHandlerInContainer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "a_requesthandler" is not a callable');

        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'a_requesthandler' => function ($container) {
                    return new RequestHandlerTester($container->get(ResponseFactoryInterface::class));
                },
            ]
        );
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $resolver->resolveCallable('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
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
        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable($obj);
    }

    public function testNotObjectInContainerThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "callable_service" is not a callable');

        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'callable_service' => fn () => 'NOT AN OBJECT',
            ]
        );
        $app = $builder->build();

        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable('callable_service');
    }

    public function testMethodNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The method "notFound" does not exists');

        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                'callable_service' => fn () => new CallableTester(),
            ]
        );
        $app = $builder->build();

        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable('callable_service:notFound');
    }

    public function testFunctionNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'notFound'");

        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable('notFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $builder = new AppBuilder();
        $app = $builder->build();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable(['Unknown', 'notFound']);
    }
}

function testAdvancedCallable()
{
    return true;
}
