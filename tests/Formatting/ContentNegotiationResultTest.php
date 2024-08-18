<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use PHPUnit\Framework\TestCase;
use Slim\Formatting\ContentNegotiationResult;

class ContentNegotiationResultTest extends TestCase
{
    public function testGetMediaType()
    {
        $mediaType = 'application/json';
        $handler = function () {
            return 'Handled';
        };

        $contentNegotiationResult = new ContentNegotiationResult($mediaType, $handler);

        $this->assertEquals($mediaType, $contentNegotiationResult->getMediaType());
    }

    public function testGetHandler()
    {
        $mediaType = 'application/json';
        $handler = function () {
            return 'Handled';
        };

        $contentNegotiationResult = new ContentNegotiationResult($mediaType, $handler);

        $retrievedHandler = $contentNegotiationResult->getHandler();

        $this->assertIsCallable($retrievedHandler);
        $this->assertEquals('Handled', $retrievedHandler());
    }

    public function testHandlerExecution()
    {
        $mediaType = 'application/xml';
        $handler = function () {
            return '<response>Handled</response>';
        };

        $contentNegotiationResult = new ContentNegotiationResult($mediaType, $handler);

        $retrievedHandler = $contentNegotiationResult->getHandler();

        $this->assertIsCallable($retrievedHandler);
        $this->assertEquals('<response>Handled</response>', $retrievedHandler());
    }
}
