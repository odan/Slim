<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use Psr\Http\Message\ServerRequestInterface;

use function explode;
use function strtolower;

final class MediaTypeDetector
{
    /**
     * Determine which content type we know about is wanted Accept header.
     *
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    public function detect(ServerRequestInterface $request): array
    {
        $mediaTypes = $this->parseAcceptHeader($request->getHeaderLine('Accept'));

        if (!$mediaTypes) {
            $mediaTypes = $this->parseContentType($request->getHeaderLine('Content-Type'));
        }

        return $mediaTypes;
    }

    private function parseAcceptHeader(string $accept = null): array
    {
        $acceptTypes = $accept ? explode(',', $accept) : [];

        // Normalize types
        $cleanTypes = [];
        foreach ($acceptTypes as $type) {
            $tokens = explode(';', $type);
            $name = trim(strtolower(reset($tokens)));
            $cleanTypes[$name] = $name;
        }

        return $cleanTypes;
    }

    private function parseContentType(string $contentType = null): array
    {
        $parts = explode(';', $contentType ?? '');

        // @phpstan-ignore-next-line
        if (!$parts) {
            return [];
        }

        $name = strtolower(trim($parts[0]));

        return $name ? [$name => $name] : [];
    }
}
