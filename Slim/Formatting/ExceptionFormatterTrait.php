<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Formatting;

use Slim\Exception\HttpException;
use Throwable;

trait ExceptionFormatterTrait
{
    private string $defaultErrorTitle = 'Application Error';

    private string $defaultErrorDescription = 'A website error has occurred. Sorry for the temporary inconvenience.';

    private function getErrorTitle(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getTitle();
        }

        return $this->defaultErrorTitle;
    }

    private function getErrorDescription(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getDescription();
        }

        return $this->defaultErrorDescription;
    }
}
