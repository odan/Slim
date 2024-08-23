<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Tests\Traits\AppTestTrait;

use function strtolower;

final class AppFactoryTest extends TestCase
{
    use AppTestTrait;

    #[DataProvider('upperCaseRequestMethodsProvider')]
    public function testDefault(string $method): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();
        $app->addErrorMiddleware();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest($method, '/');

        $methodName = strtolower($method);
        $app->$methodName('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });
        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public static function upperCaseRequestMethodsProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['OPTIONS'],
        ];
    }
}
