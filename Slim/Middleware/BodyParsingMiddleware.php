<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\ContentNegotiatorInterface;

use function is_array;
use function is_object;
use function json_decode;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function parse_str;
use function simplexml_load_string;

final class BodyParsingMiddleware implements MiddlewareInterface
{
    private ContentNegotiatorInterface $contentNegotiator;

    public function __construct(ContentNegotiatorInterface $contentNegotiator)
    {
        $this->contentNegotiator = $contentNegotiator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        if (empty($parsedBody)) {
            $parsedBody = $this->parseBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    /**
     * @param string $mediaType The HTTP media type (excluding content-type params)
     * @param callable $callable The callable that returns parsed contents for media type
     */
    public function registerBodyParser(string $mediaType, callable $callable): self
    {
        $this->contentNegotiator->setHandler($mediaType, $callable);

        return $this;
    }

    public function registerDefaultBodyParsers(): void
    {
        $this->registerBodyParser('application/json', function ($input) {
            $result = json_decode($input, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $this->registerBodyParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);

            return $data;
        });

        $xmlCallable = function ($input) {
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);

            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            if ($result === false) {
                return null;
            }

            return $result;
        };

        $this->registerBodyParser('application/xml', $xmlCallable);
        $this->registerBodyParser('text/xml', $xmlCallable);
    }

    private function parseBody(ServerRequestInterface $request): array|object|null
    {
        $negotiationResult = $this->contentNegotiator->negotiate($request);

        // Invoke the parser
        $parsed = call_user_func(
            $negotiationResult->getHandler(),
            (string)$request->getBody()
        );

        if ($parsed === null || is_object($parsed) || is_array($parsed)) {
            return $parsed;
        }

        throw new RuntimeException(
            'Request body media type parser return value must be an array, an object, or null'
        );
    }
}
