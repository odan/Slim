<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use DI\Container;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use Slim\Builder\AppBuilder;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

use function ob_get_contents;

final class OutputBufferingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testStyleDefaultValid()
    {
        $middleware = new OutputBufferingMiddleware($this->getStreamFactory());

        $reflectionProperty = new ReflectionProperty($middleware, 'style');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($middleware);

        $this->assertSame('append', $value);
    }

    public function testStyleCustomValid()
    {
        $middleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $reflectionProperty = new ReflectionProperty($middleware, 'style');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($middleware);

        $this->assertSame('prepend', $value);
    }

    public function testStyleCustomInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        new OutputBufferingMiddleware($this->getStreamFactory(), 'foo');
    }

    public function testAppend()
    {
        $builder = new AppBuilder();
        $builder->setSettings(['display_error_details' => true]);
        $app = $builder->build();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);
        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        $outputBufferingMiddleware = new OutputBufferingMiddleware($streamFactory, 'append');
        $app->add($outputBufferingMiddleware);

        $middleware = function () use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $app->add($middleware);

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('BodyTest', (string)$response->getBody());
    }

    public function testPrepend()
    {
        $responseFactory = $this->getResponseFactory();
        $middleware = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $outputBufferingMiddleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($outputBufferingMiddleware);
        $response = $middlewareDispatcher->handle($request);

        $this->assertSame('TestBody', (string)$response->getBody());
    }

    public function testOutputBufferIsCleanedWhenThrowableIsCaught()
    {
        $this->getResponseFactory();
        $test = $this;
        $middleware = (function ($request, $handler) use ($test) {
            echo 'Test';
            $test->assertSame('Test', ob_get_contents());
            throw new Exception('Oops...');
        });
        $outputBufferingMiddleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            new Container()
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($outputBufferingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
        } catch (Exception $e) {
            $this->assertSame('', ob_get_contents());
        }
    }
}
