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
use Psr\Http\Message\StreamFactoryInterface;
use Slim\App;
use Slim\Builder\AppBuilder;
use Slim\Container\GuzzleDefinitions;
use Slim\Container\HttpSoftDefinitions;
use Slim\Container\LaminasDiactorosDefinitions;
use Slim\Container\NyholmDefinitions;
use Slim\Container\SlimHttpDefinitions;
use Slim\Container\SlimPsr7Definitions;
use Slim\Interfaces\ContainerResolverInterface;

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

    protected function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->container
            ->get(ResponseFactoryInterface::class)
            ->createResponse($statusCode, $reasonPhrase);
    }

    protected function assertJsonResponse(mixed $expected, ResponseInterface $actual, string $message = ''): void
    {
        self::assertThat(
            json_decode((string)$actual->getBody(), true),
            new IsIdentical($expected),
            $message,
        );
    }

    public static function httpDefinitionsProvider(): array
    {
        return [
            'GuzzleDefinitions' => [GuzzleDefinitions::class],
            'HttpSoftDefinitions' => [HttpSoftDefinitions::class],
            'LaminasDiactorosDefinitions' => [LaminasDiactorosDefinitions::class],
            'NyholmDefinitions' => [NyholmDefinitions::class],
            'SlimHttpDefinitions' => [SlimHttpDefinitions::class],
            'SlimPsr7Definitions' => [SlimPsr7Definitions::class],
        ];
    }
}
