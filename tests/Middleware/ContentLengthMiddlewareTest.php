<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Builder\AppBuilder;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class ContentLengthMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testAddsContentLength()
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('4', $response->getHeaderLine('Content-Length'));
        $this->assertSame('Body', (string)$response->getBody());
    }
}
