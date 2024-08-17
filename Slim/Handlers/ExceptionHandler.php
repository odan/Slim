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
use Slim\Interfaces\ContentNegotiatorInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
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

    private bool $displayErrorDetails = false;

    private ContentNegotiatorInterface $contentNegotiator;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ContentNegotiatorInterface $contentNegotiator
    ) {
        $this->responseFactory = $responseFactory;
        $this->contentNegotiator = $contentNegotiator;
    }

    public function __invoke(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $statusCode = $this->determineStatusCode($request, $exception);
        $negotiationResult = $this->contentNegotiator->negotiate($request);
        $response = $this->createResponse($statusCode, $negotiationResult->getMediaType(), $exception);

        // Invoke the formatter
        return call_user_func(
            $negotiationResult->getHandler(),
            $request,
            $response,
            $exception,
            $this->displayErrorDetails
        );
    }

    public function setDisplayErrorDetails(bool $displayErrorDetails): self
    {
        $this->displayErrorDetails = $displayErrorDetails;

        return $this;
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
}
