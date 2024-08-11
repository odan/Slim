<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Strategies;

use Invoker\Exception\NotEnoughParametersException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Strategies\RequestResponseTypedArgs;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseTypedArgsTest extends TestCase
{
    use AppTestTrait;

    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    public function setUp(): void
    {
        $this->setUpApp();
        $this->request = $this->createServerRequest();
        $this->response = $this->createResponse();
        $this->invocationStrategy = $this->container->get(RequestResponseTypedArgs::class);
    }

    public function testCallingWithEmptyArguments()
    {
        $args = [];

        $callback = function ($request, $response) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);

            return $response;
        };

        $this->assertSame(
            $this->response,
            ($this->invocationStrategy)($callback, $this->request, $this->response, $args)
        );
    }

    // https://github.com/slimphp/Slim/issues/3198
    public function testCallingWithKnownArguments()
    {
        $args = [
            'name' => 'john',
            'id' => '123',
        ];

        $callback = function ($request, $response, string $name, int $id) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame('john', $name);
            $this->assertSame(123, $id);

            return $response;
        };

        $this->assertSame(
            $this->response,
            ($this->invocationStrategy)($callback, $this->request, $this->response, $args)
        );
    }

    public function testCallingWithOptionalArguments()
    {
        $args = [
            'name' => 'world',
        ];

        $callback = function ($request, $response, string $greeting = 'Hello', string $name = 'Rob') use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($greeting, 'Hello');
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame(
            $this->response,
            ($this->invocationStrategy)($callback, $this->request, $this->response, $args)
        );
    }

    public function testCallingWithNotEnoughParameters()
    {
        $this->expectException(NotEnoughParametersException::class);
        $args = [
            'greeting' => 'hello',
        ];

        $callback = function ($request, $response, $arguments) use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($args, $arguments);

            return $response;
        };

        $this->assertSame(
            $this->response,
            ($this->invocationStrategy)($callback, $this->request, $this->response, $args)
        );
    }
}
