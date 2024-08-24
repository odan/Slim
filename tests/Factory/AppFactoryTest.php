<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Tests\Traits\AppTestTrait;

final class AppFactoryTest extends TestCase
{
    use AppTestTrait;

    public function testWithMiddleware(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();
        $app->addErrorMiddleware();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });
        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testWithErrorMiddlewareDisplayErrorDetails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Displaying error details must be configured in the App settings');

        $app = AppFactory::create();
        $app->addErrorMiddleware(true);
    }

    public function testWithErrorMiddlewareLogErrorDetails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Logging error details without a logger is not supported');

        $app = AppFactory::create();
        $app->addErrorMiddleware(false, false, true);
    }

    public function testWithErrorMiddlewareLogErrors(): void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(false, true, false);
        $this->assertTrue(true);
    }

    public function testWithErrorMiddlewareWithLogger(): void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(false, true, false, $this->createMock(LoggerInterface::class));
        $this->assertTrue(true);
    }
}
