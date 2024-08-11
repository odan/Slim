<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

final class HttpNotFoundException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 404;

    /**
     * @var string
     */
    protected $message = 'Not found.';

    protected string $title = '404 Not Found';
    protected string $description = 'The requested resource could not be found. Please verify the URI and try again.';
}
