<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Stringable;

/**
 * Logs messages to the standard output stream (stdout).
 *
 * This logger writes messages to `php://stdout`, ensuring that they are non-blocking
 * and truncated to 1024 characters.
 *
 * Null characters are removed from the messages to prevent issues with logging.
 */
final class StdOutLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $stream = fopen('php://stdout', 'w');

        if ($stream === false) {
            return;
        }

        // Set the stream to non-blocking mode
        stream_set_blocking($stream, false);

        // Replace all null characters with an empty string
        // and limit the message to 1024 characters
        $message = str_replace("\0", '', substr($message, 0, 1024));

        fwrite($stream, $message);
        fclose($stream);
    }
}
