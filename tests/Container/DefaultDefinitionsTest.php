<?php

declare(strict_types=1);

namespace Slim\Tests\Container;

use DI\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Container\DefaultDefinitions;
use Slim\Container\NyholmDefinitions;
use Slim\Container\SlimHttpDefinitions;
use Slim\Container\SlimPsr7Definitions;
use Slim\Emitter\ResponseEmitter;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Router;
use Slim\Strategies\RequestResponse;

final class DefaultDefinitionsTest extends TestCase
{
    public function testSettings(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $settings = $container->get('settings');
        $expected = [
            'display_error_details' => false,
        ];

        $this->assertSame($expected, $settings);
    }

    public function testApp(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $app = $container->get(App::class);

        $this->assertInstanceOf(App::class, $app);
    }

    public function testContainerResolverInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $resolver = $container->get(ContainerResolverInterface::class);

        $this->assertInstanceOf(ContainerResolverInterface::class, $resolver);
    }

    public function testRequestHandlerInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $requestHandler = $container->get(RequestHandlerInterface::class);

        $this->assertInstanceOf(RequestHandlerInterface::class, $requestHandler);
        $this->assertInstanceOf(MiddlewareRequestHandler::class, $requestHandler);
    }

    public function testServerRequestFactoryInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
    }

    public function testServerRequestFactoryInterfaceWithSlimDecoratedServerRequestFactory(): void
    {
        $definitions = call_user_func(new DefaultDefinitions());
        $definitions = array_merge($definitions, call_user_func(new SlimHttpDefinitions()));

        $container = new Container($definitions);
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
    }

    public function testServerRequestFactoryInterfaceWithSlimServerRequestFactory(): void
    {
        $definitions = call_user_func(new DefaultDefinitions());
        $definitions = array_merge($definitions, call_user_func(new SlimPsr7Definitions()));

        $container = new Container($definitions);
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
        $this->assertInstanceOf(\Slim\Psr7\Factory\ServerRequestFactory::class, $requestFactory);
    }

    public function testServerRequestFactoryInterfaceWithNyholmServerRequestFactory(): void
    {
        $definitions = call_user_func(new DefaultDefinitions());
        $definitions = array_merge($definitions, call_user_func(new NyholmDefinitions()));

        $container = new Container($definitions);
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
        $this->assertInstanceOf(Psr17Factory::class, $requestFactory);
    }

    public function testResponseFactoryInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);
    }

    public function testStreamFactoryInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $streamFactory = $container->get(StreamFactoryInterface::class);

        $this->assertInstanceOf(StreamFactoryInterface::class, $streamFactory);
    }

    public function testUriFactoryInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $uriFactory = $container->get(UriFactoryInterface::class);

        $this->assertInstanceOf(UriFactoryInterface::class, $uriFactory);
    }

    public function testUploadedFileFactoryInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $uploadedFileFactory = $container->get(UploadedFileFactoryInterface::class);

        $this->assertInstanceOf(UploadedFileFactoryInterface::class, $uploadedFileFactory);
    }

    public function testEmitterInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $emitter = $container->get(EmitterInterface::class);

        $this->assertInstanceOf(ResponseEmitter::class, $emitter);
    }

    public function testRouter(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $router = $container->get(Router::class);

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRequestHandlerInvocationStrategyInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $invocationStrategy = $container->get(RequestHandlerInvocationStrategyInterface::class);

        $this->assertInstanceOf(RequestResponse::class, $invocationStrategy);
    }

    public function testExceptionHandlingMiddleware(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $exceptionHandlingMiddleware = $container->get(ExceptionHandlingMiddleware::class);

        $this->assertInstanceOf(ExceptionHandlingMiddleware::class, $exceptionHandlingMiddleware);
    }

    public function testBodyParsingMiddleware(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $bodyParsingMiddleware = $container->get(BodyParsingMiddleware::class);

        $this->assertInstanceOf(BodyParsingMiddleware::class, $bodyParsingMiddleware);
    }

    public function testLoggerInterface(): void
    {
        $container = new Container((new DefaultDefinitions())->__invoke());
        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
}
