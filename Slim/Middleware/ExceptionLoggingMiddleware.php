<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

final class ExceptionLoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ErrorException $exception) {
            $errorLevels = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
            $level = in_array($exception->getSeverity(), $errorLevels) ? LogLevel::ERROR : LogLevel::WARNING;

            $this->logger->log($level, $exception->getMessage(), [
                'exception' => $exception,
                'request' => $request,
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception,
                'request' => $request,
            ]);

            throw $exception;
        }
    }
}
