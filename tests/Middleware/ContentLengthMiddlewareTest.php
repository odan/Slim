<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class ContentLengthMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function setUp(): void
    {
        $this->setUpApp();
    }

    public function testAddsContentLength()
    {
        $request = $this->createServerRequest();
        $responseFactory = $this->getResponseFactory();

        $mw = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');

            return $response;
        };
        $mw2 = new ContentLengthMiddleware();

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertSame('4', $response->getHeaderLine('Content-Length'));
    }
}
