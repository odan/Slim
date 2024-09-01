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
use RuntimeException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Interfaces\MediaTypeFormatterInterface;
use Slim\Media\MediaTypeDetector;
use Throwable;

/**
 * This handler determines the response based on the media type (mime)
 * specified in the HTTP request `Accept` header.
 *
 * Output formats: JSON, HTML, XML, or Plain Text.
 */
final class ExceptionHandler implements ExceptionHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    private MediaTypeDetector $mediaTypeDetector;

    private ContainerResolverInterface $resolver;

    private bool $displayErrorDetails = false;

    private string $defaultMediaType = 'text/html';

    private array $handlers = [];

    public function __construct(
        ContainerResolverInterface $resolver,
        ResponseFactoryInterface $responseFactory,
        MediaTypeDetector $mediaTypeDetector
    ) {
        $this->resolver = $resolver;
        $this->responseFactory = $responseFactory;
        $this->mediaTypeDetector = $mediaTypeDetector;
    }

    public function __invoke(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $statusCode = $this->determineStatusCode($request, $exception);
        $mediaType = $this->negotiateMediaType($request);
        $response = $this->createResponse($statusCode, $mediaType, $exception);
        $handler = $this->negotiateHandler($mediaType);

        // Invoke the handler (formatter)
        return call_user_func(
            $handler,
            $request,
            $response,
            $exception,
            $this->displayErrorDetails
        );
    }

    public function withDisplayErrorDetails(bool $displayErrorDetails): self
    {
        $clone = clone $this;
        $clone->displayErrorDetails = $displayErrorDetails;

        return $clone;
    }

    public function withDefaultMediaType(string $mediaType): self
    {
        $clone = clone $this;
        $clone->defaultMediaType = $mediaType;

        return $clone;
    }

    public function withHandler(string $mediaType, MediaTypeFormatterInterface|callable|string $handler): self
    {
        $clone = clone $this;
        $clone->handlers[$mediaType] = $handler;

        return $clone;
    }

    public function withoutHandlers(): self
    {
        $clone = clone $this;
        $clone->handlers = [];

        return $clone;
    }

    private function negotiateMediaType(ServerRequestInterface $request): string
    {
        $mediaTypes = $this->mediaTypeDetector->detect($request);

        return $mediaTypes[0] ?? $this->defaultMediaType;
    }

    /**
     * Determine which handler to use based on media type.
     */
    private function negotiateHandler(string $mediaType): callable
    {
        $handler = $this->handlers[$mediaType] ?? reset($this->handlers);

        if (!$handler) {
            throw new RuntimeException(sprintf('Exception handler for "%s" not found', $mediaType));
        }

        return $this->resolver->resolveCallable($handler);
    }

    private function determineStatusCode(ServerRequestInterface $request, Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }

        if ($request->getMethod() === 'OPTIONS') {
            return 200;
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
}
