<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Traits;

use PHPUnit\Framework\Constraint\IsIdentical;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Builder\AppBuilder;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Middleware\ResponseFactoryMiddleware;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\RequestHandler\Runner;

trait AppTestTrait
{
    private App $app;

    private ContainerInterface $container;

    protected function createApp(array $definitions = []): App
    {
        $builder = new AppBuilder();
        $builder->setDefinitions($definitions);

        return $builder->build();
    }

    protected function setUpApp(array $definitions = []): void
    {
        $builder = new AppBuilder();
        $builder->setDefinitions($definitions);
        $this->app = $builder->build();
        $this->container = $this->app->getContainer();
    }

    protected function createContainer(): ContainerInterface
    {
        return (new AppBuilder())->build()->getContainer();
    }

    /**
     * Create a request handler that simply assigns the $request that it receives to a public property
     * of the returned response, so that we can then inspect that request.
     */
    protected function createRequestHandler(): RequestHandlerInterface
    {
        return $this->container->get(MiddlewareRequestHandler::class);
    }

    protected function createResponseFactoryMiddleware(): ResponseFactoryMiddleware
    {
        return $this->container->get(ResponseFactoryMiddleware::class);
    }

    protected function createRunner(array $queue): RequestHandlerInterface
    {
        return new Runner($queue);
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }

    protected function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->container->get(ServerRequestFactoryInterface::class);
    }

    protected function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->container->get(ResponseFactoryInterface::class);
    }

    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->container->get(StreamFactoryInterface::class);
    }

    protected function getCallableResolver(ContainerInterface $container = null): ContainerResolverInterface
    {
        return $this->container->get(ContainerResolverInterface::class);
    }

    protected function createServerRequest(
        string $method = 'GET',
        string $uri = '/',
        array $data = []
    ): ServerRequestInterface {
        return $this->container
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest($method, $uri, $data);
    }

    protected function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->container
            ->get(ResponseFactoryInterface::class)
            ->createResponse($statusCode, $reasonPhrase);
    }

    protected function createStream(string $contents = ''): StreamInterface
    {
        return $this->container
            ->get(StreamFactoryInterface::class)
            ->createStream($contents);
    }

    protected function assertJsonResponse(mixed $expected, ResponseInterface $actual, string $message = ''): void
    {
        self::assertThat(
            json_decode((string)$actual->getBody(), true),
            new IsIdentical($expected),
            $message,
        );
    }
}
