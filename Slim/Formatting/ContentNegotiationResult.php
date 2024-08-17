<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

final class ContentNegotiationResult
{
    private string $mediaType;

    /** @var callable */
    private $handler;

    public function __construct(string $contentType, callable $handler)
    {
        $this->mediaType = $contentType;
        $this->handler = $handler;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getHandler(): callable
    {
        return $this->handler;
    }
}
