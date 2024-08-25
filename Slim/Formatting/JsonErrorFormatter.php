<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Constants\MediaType;
use Slim\Interfaces\MediaTypeFormatterInterface;
use Slim\Renderers\JsonRenderer;
use Throwable;

use function get_class;

/**
 * Generates a JSON problem details response.
 *
 * Problem Details rfc7807:
 * https://datatracker.ietf.org/doc/html/rfc7807
 */
final class JsonErrorFormatter implements MediaTypeFormatterInterface
{
    use ExceptionFormatterTrait;

    private JsonRenderer $jsonRenderer;

    private string $contentType = MediaType::APPLICATION_PROBLEM_JSON;

    public function __construct(JsonRenderer $jsonRenderer)
    {
        $this->jsonRenderer = $jsonRenderer->withContentType($this->contentType);
    }

    /**
     * Change the content type of the response
     */
    public function withContentType(string $type): self
    {
        $clone = clone $this;
        $clone->jsonRenderer = $clone->jsonRenderer->withContentType($type);

        return $clone;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface {
        $error = [
            'type' => 'urn:ietf:rfc:7807',
            'title' => $this->getErrorTitle($exception),
            'status' => $response->getStatusCode(),
        ];

        if ($displayErrorDetails) {
            $error['detail'] = $this->getErrorDescription($exception);

            $error['exceptions'] = [];
            do {
                $error['exceptions'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }

        return $this->jsonRenderer->json($response, $error);
    }

    private function formatExceptionFragment(Throwable $exception): array
    {
        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();

        return [
            'type' => get_class($exception),
            'code' => $code,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }
}
