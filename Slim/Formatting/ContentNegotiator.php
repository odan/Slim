<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\ContentNegotiatorInterface;
use Slim\Interfaces\MediaTypeFormatterInterface;
use UnexpectedValueException;

use function explode;
use function strtolower;

/**
 * This handler determines the response based on the media type (mime)
 * specified in the HTTP request `Accept` header.
 *
 * Output formats: JSON, HTML, XML, or Plain Text.
 */
final class ContentNegotiator implements ContentNegotiatorInterface
{
    private ContainerResolverInterface $resolver;

    private array $formatters;

    public function __construct(ContainerResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function negotiate(ServerRequestInterface $request): ContentNegotiationResult
    {
        if (empty($this->formatters)) {
            throw new UnexpectedValueException('There is no content negotiation formatter defined');
        }

        $mediaType = $this->negotiateMediaType($request);
        $renderer = $this->negotiateFormatter($mediaType);

        return new ContentNegotiationResult($mediaType, $renderer);
    }

    public function setFormatter(string $mediaType, MediaTypeFormatterInterface|callable|string $handler): self
    {
        $this->formatters[$mediaType] = $handler;

        return $this;
    }

    public function clearFormatters(): self
    {
        $this->formatters = [];

        return $this;
    }

    /**
     * Determine which content type we know about is wanted Accept header.
     *
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    private function negotiateMediaType(ServerRequestInterface $request): string
    {
        $formatterTypes = array_keys($this->formatters);

        $mediaTypes = $this->parseAcceptHeader($request->getHeaderLine('Accept'));

        if (!$mediaTypes) {
            $mediaTypes = $this->parseContentType($request->getHeaderLine('Content-Type'));
        }

        // Use the order of definitions
        foreach ($formatterTypes as $mediaType) {
            if (isset($mediaTypes[$mediaType])) {
                return $mediaType;
            }
        }

        // No direct match is found. Check for +json or +xml.
        foreach ($mediaTypes as $type) {
            if (preg_match('/\+(json|xml)/', $type, $matches)) {
                $mediaType = 'application/' . $matches[1];
                if (isset($formatterTypes[$mediaType])) {
                    return $mediaType;
                }
            }
        }

        return reset($formatterTypes);
    }

    /**
     * Determine which renderer to use based on media type.
     */
    private function negotiateFormatter(string $mediaType): callable
    {
        $formatter = $this->formatters[$mediaType] ?? reset($this->formatters);

        return $this->resolver->resolveCallable($formatter);
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

        return [$name => $name];
    }
}
