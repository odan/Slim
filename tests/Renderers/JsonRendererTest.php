<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Renderers;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Renderers\JsonRenderer;

class JsonRendererTest extends TestCase
{
    private function createStreamFactory(): StreamFactoryInterface
    {
        $app = (new AppBuilder())->build();

        return $app->getContainer()->get(StreamFactoryInterface::class);
    }

    public function testJsonRendersCorrectly(): void
    {
        $app = (new AppBuilder())->build();
        $renderer = $app->getContainer()->get(JsonRenderer::class);

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        // Mock JSON data
        $jsonData = ['key' => 'value'];
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;
        $jsonString = json_encode($jsonData, $jsonOptions);

        $response = $renderer->json($response, $jsonData);

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($jsonString, (string)$response->getBody());
    }

    public function testSetContentType(): void
    {
        $app = (new AppBuilder())->build();
        $renderer = $app->getContainer()->get(JsonRenderer::class);

        $renderer = $renderer->withContentType('application/vnd.api+json');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();
        $response = $renderer->json($response, ['key' => 'value']);

        $this->assertEquals('application/vnd.api+json', $response->getHeaderLine('Content-Type'));
    }

    public function testSetJsonOptions(): void
    {
        $app = (new AppBuilder())->build();
        $renderer = $app->getContainer()->get(JsonRenderer::class);

        $renderer = $renderer->withJsonOptions(JSON_UNESCAPED_UNICODE);

        // Mock JSON data
        $jsonData = ['key' => 'value'];
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE);

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $response = $renderer->json($response, $jsonData);

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals($jsonString, (string)$response->getBody());
    }
}
