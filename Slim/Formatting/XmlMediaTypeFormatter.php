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
use Slim\Interfaces\MediaTypeFormatterInterface;
use Throwable;

use function get_class;

/**
 * Generates a XML problem details response.
 *
 * Problem Details rfc7807:
 * https://datatracker.ietf.org/doc/html/rfc7807#page-14
 */
final class XmlMediaTypeFormatter implements MediaTypeFormatterInterface
{
    use ExceptionFormatterTrait;

    private string $contentType = 'application/problem+xml';

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        // Create the root element
        $problem = $doc->createElement('problem');
        $doc->appendChild($problem);

        // Namespace
        $problem->setAttribute('xmlns', 'urn:ietf:rfc:7807');

        // Add title element
        $errorTitle = $this->getErrorTitle($exception);
        $title = $doc->createElement('title', $errorTitle);
        $problem->appendChild($title);

        // Add the status element
        $status = $doc->createElement('status', (string)$response->getStatusCode());
        $problem->appendChild($status);

        // Add details for each exception
        if ($displayErrorDetails) {
            $exceptions = $doc->createElement('exceptions');
            $problem->appendChild($exceptions);

            do {
                $error = $doc->createElement('exception');
                $exceptions->appendChild($error);

                $type = $doc->createElement('type', get_class($exception));
                $error->appendChild($type);

                $errorCode = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
                $code = $doc->createElement('code', (string)$errorCode);
                $error->appendChild($code);

                $message = $doc->createElement('message', $exception->getMessage());
                $error->appendChild($message);

                $file = $doc->createElement('file', $exception->getFile());
                $error->appendChild($file);

                $line = $doc->createElement('line', (string)$exception->getLine());
                $error->appendChild($line);

                $trace = $doc->createElement('trace', $exception->getTraceAsString());
                $error->appendChild($trace);
            } while ($exception = $exception->getPrevious());
        }

        $response->getBody()->write((string)$doc->saveXML());

        return $response->withHeader('Content-Type', $this->contentType);
    }

    /**
     * Change the content type of the response
     */
    public function setContentType(string $type): self
    {
        $this->contentType = $type;

        return $this;
    }
}
