<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Handlers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Interfaces\ExceptionRendererInterface;
use Throwable;

use function explode;
use function strtolower;

/**
 * This handler determines the response based on the media type (mime)
 * specified in the HTTP request `Accept` header.
 *
 * Output formats: JSON, HTML, XML, or Plain Text.
 */
final class ExceptionHandler implements ExceptionHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    private ContainerResolverInterface $resolver;

    /* @var array<ExceptionHandlerInterface|callable|string> */
    private array $renderers = [];

    private string $defaultMediaType = 'application/json';

    private bool $displayErrorDetails = false;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ContainerResolverInterface $resolver
    ) {
        $this->resolver = $resolver;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $statusCode = $this->determineStatusCode($request, $exception);
        $mediaType = $this->determineMediaType($request);
        $response = $this->createResponse($statusCode, $mediaType, $exception);
        $renderer = $this->determineRenderer($mediaType);

        // Invoke the renderer
        /** @var ResponseInterface $response */
        $response = call_user_func($renderer, $request, $response, $exception, $this->displayErrorDetails);

        return $response;
    }

    public function setDisplayErrorDetails(bool $displayErrorDetails): self
    {
        $this->displayErrorDetails = $displayErrorDetails;

        return $this;
    }

    public function registerRenderer(string $mediaType, ExceptionRendererInterface|callable|string $handler): self
    {
        $this->renderers[$mediaType] = $handler;

        return $this;
    }

    public function setDefaultMediaType(string $mediaType): self
    {
        $this->defaultMediaType = $mediaType;

        return $this;
    }

    /**
     * Determine which renderer to use based on media type.
     */
    private function determineRenderer(string $mediaType): ExceptionRendererInterface
    {
        $renderer = $this->renderers[$mediaType] ?? $this->renderers[$this->defaultMediaType];

        return $this->resolver->resolveCallable($renderer);
    }

    /**
     * Determine which content type we know about is wanted Accept header.
     *
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    protected function determineMediaType(ServerRequestInterface $request): string
    {
        $mediaTypes = $this->parseAcceptHeader($request->getHeaderLine('Accept'));

        if (!$mediaTypes) {
            $mediaTypes = $this->parseContentType($request->getHeaderLine('Content-Type'));
        }

        // Use the order of definitions
        foreach ($this->renderers as $mediaType => $_) {
            if (isset($mediaTypes[$mediaType])) {
                return $mediaType;
            }
        }

        // No direct match is found. Check for +json or +xml.
        foreach ($mediaTypes as $type) {
            if (preg_match('/\+(json|xml)/', $type, $matches)) {
                $mediaType = 'application/' . $matches[1];
                if (isset($this->renderers[$mediaType])) {
                    return $mediaType;
                }
            }
        }

        return $this->defaultMediaType;
    }

    private function determineStatusCode(ServerRequestInterface $request, Throwable $exception): int
    {
        if ($request->getMethod() === 'OPTIONS') {
            return 200;
        }

        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }

        return 500;
    }

    private function createResponse(
        int $statusCode,
        string $contentType,
        Throwable $exception
    ): ResponseInterface {
        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', $contentType);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        return $response;
    }

    public function parseAcceptHeader(string $accept = null): array
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
