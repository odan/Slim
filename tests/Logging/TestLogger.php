<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Test\Logging;

use Psr\Log\AbstractLogger;
use Stringable;

final class TestLogger extends AbstractLogger
{
    private array $logs = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $level = (string)$level;
        if (!isset($this->logs[$level])) {
            $this->logs[$level] = [];
        }

        $this->logs[$level][] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function hasErrorRecords(): bool
    {
        return !empty($this->logs['error']);
    }
}
