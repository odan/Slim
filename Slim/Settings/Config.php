<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Settings;

use Slim\Interfaces\ConfigInterface;

final class Config implements ConfigInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key] ?? $default;
        }

        $result = $this->data;

        foreach (explode('.', $key) as $offset) {
            if (!isset($result[$offset])) {
                return $default;
            }
            $result = $result[$offset];
        }

        return $result;
    }
}
