<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Builder;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Builder\AppBuilder;
use Slim\Container\DefaultDefinitions;
use Slim\Container\HttpDefinitions;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class AppBuilderTest extends TestCase
{
    use AppTestTrait;

    public function testSetSettings(): void
    {
        $builder = new AppBuilder();
        $builder->setSettings([
            'key' => 'value',
        ]);
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write($this->get('settings')['key']);

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('value', (string)$response->getBody());
    }

    public function testSetSettingsMerged(): void
    {
        $builder = new AppBuilder();
        $builder->setSettings([
            'key' => 'value',
            'key2' => 'value2',
        ]);
        $builder->setSettings([
            'key' => 'value3',
        ]);
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $settings = $this->get('settings');
            $response->getBody()->write(json_encode($settings));

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('{"key":"value3"}', (string)$response->getBody());
    }

    public function testSetContainerFactory(): void
    {
        $builder = new AppBuilder();
        $builder->setContainerFactory(function () {
            $defaults = (new DefaultDefinitions())->__invoke();
            $defaults = array_merge($defaults, (new HttpDefinitions())->__invoke());

            $defaults['foo'] = 'bar';

            return new Container($defaults);
        });
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write($this->get('foo'));

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('bar', (string)$response->getBody());
    }

    public function testMiddlewareOrderFifo(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('OK');

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('OK', (string)$response->getBody());
    }
}
