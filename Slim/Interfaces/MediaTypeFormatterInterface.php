<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface MediaTypeFormatterInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface;
}
