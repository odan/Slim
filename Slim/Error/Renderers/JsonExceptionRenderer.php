<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Error\Renderers;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ExceptionRendererInterface;
use Slim\Renderers\JsonRenderer;
use Throwable;

use function get_class;

/**
 * Formats exceptions into a JSON response.
 */
final class JsonExceptionRenderer implements ExceptionRendererInterface
{
    use ExceptionRendererTrait;

    private JsonRenderer $jsonRenderer;

    public function __construct(JsonRenderer $jsonRenderer)
    {
        $this->jsonRenderer = $jsonRenderer;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface {
        $error = ['message' => $this->getErrorTitle($exception)];

        if ($displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($exception);
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
        ];
    }
}
