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

    public function __construct(string $contentType)
    {
        $this->mediaType = $contentType;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }
}
