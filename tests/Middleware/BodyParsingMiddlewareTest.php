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
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Interfaces\ContentNegotiatorInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ResponseFactoryMiddleware;
use Slim\RequestHandler\Runner;
use Slim\Tests\Traits\AppTestTrait;

use function simplexml_load_string;

final class BodyParsingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    #[DataProvider('parsingProvider')]
    public function testParsing($contentType, $body, $expected)
    {
        $builder = new AppBuilder();

        // Replace or change the PSR-17 factory because slim/http has its own parser
        $builder->setDefinitions(
            [
                ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                    return $container->get(SlimPsr17Factory::class);
                },
            ]
        );
        $app = $builder->build();

        $responseFactory = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        $test = $this;
        $middlewares = [
            $app->getContainer()->get(BodyParsingMiddleware::class),
            $this->createCallbackMiddleware(function (ServerRequestInterface $request) use ($expected, $test) {
                $test->assertEquals($expected, $request->getParsedBody());
            }),
            $responseFactory,
        ];

        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream($body);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType)
            ->withBody($stream);

        (new Runner($middlewares))->handle($request);
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
        $builder = new AppBuilder();

        // Replace or change the PSR-17 factory because slim/http has its own parser
        $builder->setDefinitions(
            [
                ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                    return $container->get(SlimPsr17Factory::class);
                },
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $negotiator = $container->get(ContentNegotiatorInterface::class);
                    $middleware = new BodyParsingMiddleware($negotiator);
                    // $middleware->registerDefaultBodyParsers();
                    $middleware->registerBodyParser('application/vnd.api+json', function ($input) {
                        return ['data' => json_decode($input, true)];
                    });

                    return $middleware;
                },
            ]
        );
        $app = $builder->build();

        $input = '{"foo":"bar"}';
        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream($input);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/vnd.api+json;charset=utf8')
            ->withBody($stream);

        $middlewares = [];
        $middlewares[] = $app->getContainer()->get(BodyParsingMiddleware::class);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        $response = (new Runner($middlewares))->handle($request);

        $this->assertJsonResponse(['data' => ['foo' => 'bar']], $response);
        $this->assertSame(['data' => ['foo' => 'bar']], json_decode((string)$response->getBody(), true));
    }

    public function testParsingFailsWhenAnInvalidTypeIsReturned()
    {
        $this->expectException(RuntimeException::class);

        // Note: If slim/http is installed then this middleware, then getParsedBody is already filled!!!
        // So this should be tested with different psr-7 packages

        $builder = new AppBuilder();

        // Replace or change the PSR-17 factory because slim/http has its own parser
        $builder->setDefinitions(
            [
                ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                    return $container->get(SlimPsr17Factory::class);
                },
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $negotiator = $container->get(ContentNegotiatorInterface::class);
                    $middleware = new BodyParsingMiddleware($negotiator);

                    // $middleware->registerDefaultBodyParsers();
                    $middleware->registerBodyParser('application/json', function ($input) {
                        return 10; // invalid - should return null, array or object
                    });

                    return $middleware;
                },
            ]
        );
        $app = $builder->build();

        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream('{"foo":"bar"}');

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/json;charset=utf8')
            ->withHeader('Content-Type', 'application/json;charset=utf8')
            ->withBody($stream);

        $middlewares = [];
        $middlewares[] = $app->getContainer()->get(BodyParsingMiddleware::class);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        (new Runner($middlewares))->handle($request);
    }

    private function createParsedBodyMiddleware(): MiddlewareInterface
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

    private function createCallbackMiddleware(callable $callback): MiddlewareInterface
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
