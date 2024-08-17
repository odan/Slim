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
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Builder\AppBuilder;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteContext;

class RoutingResultsTest extends TestCase
{
    public function testRoutingArgumentsFromRouteContext(): void
    {
        $app = (new AppBuilder())->build();

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Define a route with arguments
        $app->get('/test/{id}', function (ServerRequestInterface $request, ResponseInterface $response) {
            $args = RouteContext::fromRequest($request)->getRoutingResults()->getRouteArguments();
            $response->getBody()->write('ID: ' . $args['id']);

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test/123');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ID: 123', (string)$response->getBody());
    }
}
