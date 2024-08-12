<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use Slim\Interfaces\MediaTypeFormatterInterface;

final class ContentNegotiationResult
{
    private string $mediaType;

    private MediaTypeFormatterInterface $formatter;

    public function __construct(string $contentType, MediaTypeFormatterInterface $formatter)
    {
        $this->mediaType = $contentType;
        $this->formatter = $formatter;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getFormatter(): MediaTypeFormatterInterface
    {
        return $this->formatter;
    }
}
