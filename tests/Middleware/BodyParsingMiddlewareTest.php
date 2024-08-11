<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

use function simplexml_load_string;

final class BodyParsingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function setUp(): void
    {
        $this->setUpApp();
    }

    protected function createRequestWithBody(string $contentType, string $body): ServerRequestInterface
    {
        $request = $this->createServerRequest('POST', '/');
        if ($contentType) {
            $request = $request->withHeader('Accept', $contentType);
            $request = $request->withHeader('Content-Type', $contentType);
        }
        if ($body) {
            $request = $request->withBody($this->createStream($body));
        }

        return $request;
    }

    #[DataProvider('parsingProvider')]
    public function testParsing($contentType, $body, $expected)
    {
        $test = $this;
        $middlewares = [
            new BodyParsingMiddleware(),
            $this->createCallbackMiddleware(function (ServerRequestInterface $request) use ($expected, $test) {
                $test->assertEquals($expected, $request->getParsedBody());
            }),
            $this->createResponseFactoryMiddleware(),
        ];

        $request = $this->createRequestWithBody($contentType, $body);
        $this->createRunner($middlewares)->handle($request);
    }

    public static function parsingProvider(): array
    {
        return [
            'form' => [
                'application/x-www-form-urlencoded;charset=utf8',
                'foo=bar',
                ['foo' => 'bar'],
            ],
            'json' => [
                'application/json',
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'json-with-charset' => [
                "application/json\t ; charset=utf8",
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'json-suffix' => [
                'application/vnd.api+json;charset=utf8',
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'xml' => [
                'application/xml',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'xml-suffix' => [
                'application/hal+xml;charset=utf8',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'text-xml' => [
                'text/xml',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'invalid-json' => [
                'application/json;charset=utf8',
                '{"foo"}/bar',
                null,
            ],
            'valid-json-but-not-an-array' => [
                'application/json;charset=utf8',
                '"foo bar"',
                null,
            ],
            'unknown-contenttype' => [
                'text/foo+bar',
                '"foo bar"',
                null,
            ],
            'empty-contenttype' => [
                '',
                '"foo bar"',
                null,
            ],
            // null is not supported anymore
            // 'no-contenttype' => [
            //    null,
            //    '"foo bar"',
            //    null,
            // ],
            'invalid-contenttype' => [
                'foo',
                '"foo bar"',
                null,
            ],
            'invalid-xml' => [
                'application/xml',
                '<person><name>John</name></invalid>',
                null,
            ],
            'invalid-textxml' => [
                'text/xml',
                '<person><name>John</name></invalid>',
                null,
            ],
        ];
    }

    public function testParsingWithARegisteredParser()
    {
        // Replace or change the PSR-17 factory because slim/http has its own parser
        $this->container->set(ServerRequestFactoryInterface::class, function (ContainerInterface $container) {
            return $container->get(SlimPsr17Factory::class);
        });

        $input = '{"foo":"bar"}';
        $request = $this->createRequestWithBody('application/vnd.api+json', $input);

        $parsers = [
            'application/vnd.api+json' => function ($input) {
                return ['data' => json_decode($input, true)];
            },
        ];

        $middlewares = [];
        $middlewares[] = new BodyParsingMiddleware($parsers);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $this->createResponseFactoryMiddleware();

        $response = $this->createRunner($middlewares)->handle($request);
        $this->assertSame(['data' => ['foo' => 'bar']], json_decode((string)$response->getBody(), true));
    }

    public function testParsingFailsWhenAnInvalidTypeIsReturned()
    {
        $this->expectException(RuntimeException::class);

        // Note: If slim/http is installed then this middleware, then getParsedBody is already filled!!!
        // So this should be tested with different psr-7 packages

        // Replace or change the PSR-17 factory because slim/http has its own parser
        $this->container->set(ServerRequestFactoryInterface::class, function (ContainerInterface $container) {
            return $container->get(SlimPsr17Factory::class);
        });

        $request = $this->createRequestWithBody('application/json;charset=utf8', '{"foo":"bar"}');

        $parsers = [
            'application/json' => function ($input) {
                return 10; // invalid - should return null, array or object
            },
        ];
        $middlewares = [];
        $middlewares[] = new BodyParsingMiddleware($parsers);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $this->createResponseFactoryMiddleware();

        $this->createRunner($middlewares)->handle($request);
    }

    private function createParsedBodyMiddleware()
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);

                // Return the parsed body
                $response->getBody()->write(json_encode($request->getParsedBody()));

                return $response;
            }
        };
    }

    private function createCallbackMiddleware(callable $callback)
    {
        return new class ($callback) implements MiddlewareInterface {
            /**
             * @var callable
             */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);

                call_user_func($this->callback, $request, $handler);

                return $response;
            }
        };
    }
}
