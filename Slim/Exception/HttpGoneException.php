<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

final class HttpGoneException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 410;

    /**
     * @var string
     */
    protected $message = 'Gone.';

    protected string $title = '410 Gone';
    protected string $description = 'The target resource is no longer available at the origin server.';
}
