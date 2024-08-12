<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;
use Throwable;

final class ExceptionHandlingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testExceptionHandlingMiddlewareHandlesException()
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);

        // Custom ExceptionHandlerInterface implementation
        $exceptionHandler = new class ($responseFactory) implements ExceptionHandlerInterface {
            private ResponseFactoryInterface $responseFactory;

            public function __construct($responseFactory)
            {
                $this->responseFactory = $responseFactory;
            }

            public function __invoke(ServerRequestInterface $request, Throwable $exception): ResponseInterface
            {
                $response = $this->responseFactory->createResponse(500, 'Internal Server Error');
                $response->getBody()->write($exception->getMessage());

                return $response;
            }
        };

        $app->add(new ExceptionHandlingMiddleware($exceptionHandler));
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function () {
            throw new RuntimeException('Something went wrong');
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertSame('Something went wrong', (string)$response->getBody());
    }

    public function testExceptionHandlingMiddlewarePassesThroughNonExceptionRequest()
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);

        // This handler should not be called in this test
        $exceptionHandler = new class ($responseFactory) implements ExceptionHandlerInterface {
            private ResponseFactoryInterface $responseFactory;

            public function __construct($responseFactory)
            {
                $this->responseFactory = $responseFactory;
            }

            public function __invoke(ServerRequestInterface $request, Throwable $exception): ResponseInterface
            {
                $response = $this->responseFactory->createResponse(500);
                $response->getBody()->write($exception->getMessage());

                return $response;
            }
        };

        $app->add(new ExceptionHandlingMiddleware($exceptionHandler));
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('Hello World', (string)$response->getBody());
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
