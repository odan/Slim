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

/**
 * This handler determines the response based on the media type (mime)
 * specified in the HTTP request `Accept` header.
 */
final class ContentNegotiator implements ContentNegotiatorInterface
{
    private ContainerResolverInterface $resolver;

    private MediaTypeDetector $mediaTypeDetector;

    private array $handlers;

    public function __construct(ContainerResolverInterface $resolver, MediaTypeDetector $mediaTypeDetector)
    {
        $this->resolver = $resolver;
        $this->mediaTypeDetector = $mediaTypeDetector;
    }

    public function negotiate(ServerRequestInterface $request): ContentNegotiationResult
    {
        if (empty($this->handlers)) {
            throw new UnexpectedValueException('There is no content negotiation handler defined');
        }

        $mediaType = $this->negotiateMediaType($request);
        $handler = $this->negotiateHandler($mediaType);

        return new ContentNegotiationResult($mediaType, $handler);
    }

    public function setHandler(string $mediaType, MediaTypeFormatterInterface|callable|string $handler): self
    {
        $this->handlers[$mediaType] = $handler;

        return $this;
    }

    public function clearFormatters(): self
    {
        $this->handlers = [];

        return $this;
    }

    /**
     * Determine which content type we know about is wanted Accept header.
     *
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    private function negotiateMediaType(ServerRequestInterface $request): string
    {
        $mediaTypes = $this->mediaTypeDetector->detect($request);

        // Use the order of definitions
        foreach (array_keys($this->handlers) as $mediaType) {
            if (isset($mediaTypes[$mediaType])) {
                return $mediaType;
            }
        }

        // No direct match is found. Check for +json or +xml.
        foreach ($mediaTypes as $type) {
            if (preg_match('/\+(json|xml)/', $type, $matches)) {
                $mediaType = 'application/' . $matches[1];
                if (isset($this->handlers[$mediaType])) {
                    return $mediaType;
                }
            }
        }

        return (string)array_key_first($this->handlers);
    }

    /**
     * Determine which renderer to use based on media type.
     */
    private function negotiateHandler(string $mediaType): callable
    {
        $formatter = $this->handlers[$mediaType] ?? reset($this->handlers);

        return $this->resolver->resolveCallable($formatter);
    }
}
