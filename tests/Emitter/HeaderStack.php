<?php

declare(strict_types=1);

namespace Slim\Tests\Emitter;

/**
 * Header test helper.
 */
final class HeaderStack
{
    /**
     * Reset state
     */
    public static function reset(): void
    {
        header_remove();
    }

    /**
     * Return the current header stack
     *
     * @return string[][]
     */
    public static function stack(): array
    {
        $headers = headers_list();

        if (!$headers && function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
        }

        $result = [];

        foreach ($headers as $header) {
            $result[] = [
                'header' => $header,
            ];
        }

        return $result;
    }
}
