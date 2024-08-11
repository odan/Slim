<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Strategies;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Strategies\RequestResponseNamedArgs;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseNamedArgsTest extends TestCase
{
    use AppTestTrait;

    private ServerRequestInterface $request;
    private ResponseInterface $response;

    public function setUp(): void
    {
        $this->setUpApp();
        $this->request = $this->createServerRequest();
        $this->response = $this->createResponse();
    }

    public function testCallingWithEmptyArguments()
    {
        $args = [];
        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithKnownArguments()
    {
        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting, string $name) use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($greeting, $args['greeting']);
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithOptionalArguments()
    {
        $args = [
            'name' => 'world',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting = 'Hello', $name = 'Rob') use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($greeting, 'Hello');
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithUnknownAndVariadic()
    {
        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, ...$arguments) use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($args, $arguments);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithMixedKnownAndUnknownParametersAndVariadic()
    {
        $known = [
            'name' => 'world',
            'greeting' => 'hello',
        ];
        $unknown = [
            'foo' => 'foo',
            'bar' => 'bar',
        ];
        $args = array_merge($known, $unknown);
        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $name, $greeting, ...$arguments) use ($known, $unknown) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($name, $known['name']);
            $this->assertSame($greeting, $known['greeting']);
            $this->assertSame($unknown, $arguments);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }
}
