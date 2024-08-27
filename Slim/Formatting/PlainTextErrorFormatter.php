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
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Constants\MediaType;
use Slim\Interfaces\MediaTypeFormatterInterface;
use Throwable;

use function get_class;
use function sprintf;

/**
 * Formats exceptions into a plain text response.
 */
final class PlainTextErrorFormatter implements MediaTypeFormatterInterface
{
    use ExceptionFormatterTrait;

    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface {
        $text = sprintf("%s\n", $this->getErrorTitle($exception));

        if ($displayErrorDetails) {
            $text .= $this->formatExceptionFragment($exception);

            while ($exception = $exception->getPrevious()) {
                $text .= "\nPrevious Exception:\n";
                $text .= $this->formatExceptionFragment($exception);
            }
        }

        $body = $this->streamFactory->createStream($text);
        $response = $response->withBody($body);

        return $response->withHeader('Content-Type', MediaType::TEXT_PLAIN);
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
