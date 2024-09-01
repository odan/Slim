<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Media\MediaTypeDetector;

class MediaTypeDetectorTest extends TestCase
{
    #[DataProvider('provideAcceptHeaderCases')]
    public function testDetectFromAcceptHeader(string $acceptHeader, array $expectedMediaTypes)
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', $acceptHeader);

        $mediaTypeDetector = new MediaTypeDetector();
        $detectedMediaTypes = $mediaTypeDetector->detect($request);

        $this->assertEquals($expectedMediaTypes, $detectedMediaTypes);
    }

    #[DataProvider('provideContentTypeCases')]
    public function testDetectFromContentTypeHeader(string $contentTypeHeader, array $expectedMediaTypes)
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', $contentTypeHeader);

        $mediaTypeDetector = new MediaTypeDetector();
        $detectedMediaTypes = $mediaTypeDetector->detect($request);

        $this->assertEquals($expectedMediaTypes, $detectedMediaTypes);
    }

    public static function provideAcceptHeaderCases(): array
    {
        return [
            ['application/json', [0 => 'application/json']],
            ['text/html', [0 => 'text/html']],
            ['application/xml, text/html', [0 => 'application/xml', 1 => 'text/html']],
            ['*/*', [0 => '*/*']],
            ['', []],
        ];
    }

    public static function provideContentTypeCases(): array
    {
        return [
            ['application/json', [0 => 'application/json']],
            ['text/html', [0 => 'text/html']],
            ['application/xml; charset=UTF-8', [0 => 'application/xml']],
            ['application/vnd.api+json', [0 => 'application/vnd.api+json']],
            ['', []],
        ];
    }
}
