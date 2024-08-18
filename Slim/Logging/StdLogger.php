<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Logs messages to the standard streams (STDOUT or STDERR).
 */
final class StdLogger extends AbstractLogger
{
    private const ERROR_LEVELS = [
        LogLevel::ERROR => 1,
        LogLevel::CRITICAL => 1,
        LogLevel::ALERT => 1,
        LogLevel::EMERGENCY => 1,
    ];

    private $stdout;

    private $stderr;

    public function __construct($stdout = STDOUT, $stderr = STDERR)
    {
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        // Replace all null characters with an empty string
        // and limit the message to 1024 characters
        $message = str_replace("\0", '', substr($message . "\n", 0, 1024));

        $stream = isset(self::ERROR_LEVELS[$level]) ? $this->stderr : $this->stdout;

        fwrite($stream, $message);
    }
}
