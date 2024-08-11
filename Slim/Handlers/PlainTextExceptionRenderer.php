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
use Throwable;

use function get_class;
use function sprintf;

/**
 * Plain Text Error Renderer.
 */
final class PlainTextExceptionRenderer implements ExceptionRendererInterface
{
    use ExceptionRendererTrait;

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $text = sprintf("%s\n", $this->getErrorTitle($exception));

        if ($displayErrorDetails) {
            $text .= $this->formatExceptionFragment($exception);

            while ($exception = $exception->getPrevious()) {
                $text .= "\nPrevious Exception:\n";
                $text .= $this->formatExceptionFragment($exception);
            }
        }

        $response->getBody()->write($text);

        return $response->withHeader('Content-Type', 'text/plain');
    }

    private function formatExceptionFragment(Throwable $exception): string
    {
        $text = sprintf("Type: %s\n", get_class($exception));

        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();

        $text .= sprintf("Code: %s\n", $code);
        $text .= sprintf("Message: %s\n", $exception->getMessage());
        $text .= sprintf("File: %s\n", $exception->getFile());
        $text .= sprintf("Line: %s\n", $exception->getLine());
        $text .= sprintf('Trace: %s', $exception->getTraceAsString());

        return $text;
    }
}
