<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Handlers;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ExceptionRendererInterface;
use Slim\Renderers\JsonRenderer;
use Throwable;

use function get_class;

/**
 * Generates a JSON problem details response.
 *
 * Problem Details rfc7807:
 * https://datatracker.ietf.org/doc/html/rfc7807
 */
final class JsonExceptionRenderer implements ExceptionRendererInterface
{
    use ExceptionRendererTrait;

    private JsonRenderer $jsonRenderer;

    private string $contentType = 'application/problem+json';

    public function __construct(JsonRenderer $jsonRenderer)
    {
        $this->jsonRenderer = $jsonRenderer->setContentType($this->contentType);
    }

    /**
     * Change the content type of the response
     */
    public function setContentType(string $type): self
    {
        $this->jsonRenderer->setContentType($type);

        return $this;
    }

    /**
     * Set options for JSON encoding
     *
     * @see https://php.net/manual/function.json-encode.php
     * @see https://php.net/manual/json.constants.php
     */
    public function setJsonOptions(int $options): self
    {
        $this->jsonRenderer->setJsonOptions($options);

        return $this;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        Throwable $exception,
        bool $displayErrorDetails
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
