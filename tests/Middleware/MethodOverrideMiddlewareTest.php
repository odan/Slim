<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Builder\AppBuilder;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class MethodOverrideMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testHeader()
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (Request $request, RequestHandler $handler) use ($responseFactory, $test) {
            $test->assertSame('PUT', $request->getMethod());

            return $responseFactory->createResponse();
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'PUT');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            new Container()
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($methodOverrideMiddleware);
        $middlewareDispatcher->handle($request);
    }

    public function testBodyParam()
    {
        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (Request $request, RequestHandler $handler) use ($responseFactory, $test) {
            $test->assertSame('PUT', $request->getMethod());

            return $responseFactory->createResponse();
        });

        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('POST', '/')
            ->withParsedBody(['_METHOD' => 'PUT']);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($methodOverrideMiddleware);
        $middlewareDispatcher->handle($request);
    }

    public function testHeaderPreferred()
    {
        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (Request $request, RequestHandler $handler) use ($responseFactory, $test) {
            $test->assertSame('DELETE', $request->getMethod());

            return $responseFactory->createResponse();
        });

        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'DELETE')
            ->withParsedBody((object)['_METHOD' => 'PUT']);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            new Container()
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($methodOverrideMiddleware);
        $middlewareDispatcher->handle($request);
    }

    public function testNoOverride()
    {
        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (Request $request, RequestHandler $handler) use ($responseFactory, $test) {
            $test->assertSame('POST', $request->getMethod());

            return $responseFactory->createResponse();
        });

        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('POST', '/');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            new Container()
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($methodOverrideMiddleware);
        $middlewareDispatcher->handle($request);
    }

    public function testNoOverrideRewindEofBodyStream()
    {
        $responseFactory = $this->getResponseFactory();
        $test = $this;
        $middleware = (function (Request $request, RequestHandler $handler) use ($responseFactory, $test) {
            $test->assertSame('POST', $request->getMethod());

            return $responseFactory->createResponse();
        });

        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('POST', '/');

        // Prophesize the body stream for which `eof()` returns `true` and the
        // `rewind()` has to be called.
        $bodyProphecy = $this->prophesize(StreamInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $bodyProphecy->eof()
            ->willReturn(true)
            ->shouldBeCalled();
        /** @noinspection PhpUndefinedMethodInspection */
        $bodyProphecy->rewind()
            ->shouldBeCalled();
        /** @var StreamInterface $body */
        $body = $bodyProphecy->reveal();
        $request = $request->withBody($body);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($methodOverrideMiddleware);
        $middlewareDispatcher->handle($request);
    }
}
