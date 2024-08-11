<?php

declare(strict_types=1);

namespace Slim\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Converts errors into `ErrorException` instances.
 */
final class ErrorHandlingMiddleware implements MiddlewareInterface
{
    private ?int $errorLevel;

    /**
     * @param int|null $errorLevel The PHP error level, e.g. E_ALL. Can be a bit mask.
     */
    public function __construct(int $errorLevel = null)
    {
        $this->errorLevel = $errorLevel;
    }

    /**
     * @throws ErrorException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->errorLevel !== null) {
            error_reporting($this->errorLevel);
        }

        set_error_handler(
            // @phpstan-ignore-next-line
            function ($severity, $message, $file, $line) {
                if ($severity) {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                }
            }
        );

        $response = $handler->handle($request);

        restore_error_handler();

        return $response;
    }
}
