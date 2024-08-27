<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use DOMDocument;
use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Constants\MediaType;
use Slim\Interfaces\MediaTypeFormatterInterface;
use Throwable;

use function get_class;

/**
 * Formats exceptions into a XML response.
 */
final class XmlErrorFormatter implements MediaTypeFormatterInterface
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
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $errorElement = $dom->createElement('error');
        $dom->appendChild($errorElement);

        $messageElement = $dom->createElement('message', $this->getErrorTitle($exception));
        $errorElement->appendChild($messageElement);

        // If error details should be displayed
        if ($displayErrorDetails) {
            do {
                $exceptionElement = $dom->createElement('exception');

                $typeElement = $dom->createElement('type', get_class($exception));
                $exceptionElement->appendChild($typeElement);

                $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
                $codeElement = $dom->createElement('code', (string)$code);
                $exceptionElement->appendChild($codeElement);

                $messageElement = $dom->createElement('message', $exception->getMessage());
                $exceptionElement->appendChild($messageElement);

                $fileElement = $dom->createElement('file', $exception->getFile());
                $exceptionElement->appendChild($fileElement);

                $lineElement = $dom->createElement('line', (string)$exception->getLine());
                $exceptionElement->appendChild($lineElement);

                $errorElement->appendChild($exceptionElement);
            } while ($exception = $exception->getPrevious());
        }

        $body = $this->streamFactory->createStream((string)$dom->saveXML());
        $response = $response->withBody($body);

        return $response->withHeader('Content-Type', MediaType::APPLICATION_XML);
    }
}
