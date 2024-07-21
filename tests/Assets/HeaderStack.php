<?php

declare(strict_types=1);

namespace Slim\Tests\Assets;

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
        $headers = headers_list() ?: xdebug_get_headers();
        $result = array();

        foreach ($headers as $header) {
            $result[] = [
                'header' => $header,
            ];
        }

        return $result;
    }
}
