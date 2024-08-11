<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class ExceptionHandlingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function setUp(): void
    {
        $this->setUpApp();
    }

    public function testDefaultMediaTypeWithoutDetails(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new RuntimeException('Test error');
        });

        $response = $app->handle($request);

        $expected = [
            'type' => 'urn:ietf:rfc:7807',
            'title' => 'Slim Application Error',
            'status' => 500,
        ];
        $this->assertJsonResponse($expected, $response);
    }

    public function testDefaultMediaTypeWithDetails(): void
    {
        $builder = new AppBuilder();
        $builder->setSettings(['display_error_details' => true]);
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new RuntimeException('Test error', 123);
        });

        $response = $app->handle($request);

        $actual = json_decode((string)$response->getBody(), true);
        $this->assertSame('urn:ietf:rfc:7807', $actual['type']);
        $this->assertSame('Slim Application Error', $actual['title']);
        $this->assertSame(500, $actual['status']);
        $this->assertSame('A website error has occurred. Sorry for the temporary inconvenience.', $actual['detail']);
        $this->assertSame(1, count($actual['exceptions']));
        $this->assertSame('RuntimeException', $actual['exceptions'][0]['type']);
        $this->assertSame(123, $actual['exceptions'][0]['code']);
        $this->assertSame('Test error', $actual['exceptions'][0]['message']);
    }
}
