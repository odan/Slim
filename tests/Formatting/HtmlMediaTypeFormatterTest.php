<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Formatting\HtmlErrorFormatter;

class HtmlMediaTypeFormatterTest extends TestCase
{
    public function testInvokeWithExceptionAndWithErrorDetails()
    {
        // Create the Slim app
        $app = (new AppBuilder())->build();

        // Create a request and response
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        $formatter = new HtmlErrorFormatter();
        $result = $formatter($request, $response, $exception, true);

        $this->assertEquals('text/html', $result->getHeaderLine('Content-Type'));

        $html = (string)$result->getBody();
        $this->assertStringContainsString('<h2>Details</h2>', $html);
        $this->assertStringContainsString('Test exception message', $html);
        $this->assertStringContainsString('<div><strong>Type:</strong> Exception</div>', $html);
    }

    public function testInvokeWithExceptionAndWithoutErrorDetails()
    {
        // Create the Slim app
        $app = (new AppBuilder())->build();

        // Create a request and response
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $body = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream('');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse()
            ->withBody($body);

        $exception = new Exception('Test exception message');

        // Instantiate the formatter and invoke it
        $formatter = new HtmlErrorFormatter();
        $result = $formatter($request, $response, $exception, false);

        // Expected HTML
        $html = (string)$result->getBody();
        $this->assertStringNotContainsString('<h2>Details</h2>', $html);
        $this->assertStringContainsString('Application Error', $html);
        $this->assertStringContainsString(
            'A website error has occurred. Sorry for the temporary inconvenience.',
            $html
        );
    }
}
