<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Slim\Configuration\Config;

final class ConfigTest extends TestCase
{
    public function testGetWithExistingKey(): void
    {
        $data = [
            'key1' => true,
            'key2' => false,
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ];

        $config = new Config($data);

        // Test retrieving a top-level key
        $this->assertTrue($config->get('key1'));
        $this->assertFalse($config->get('key2'));

        // Test retrieving a nested key
        $this->assertSame('localhost', $config->get('database.host'));
        $this->assertSame(3306, $config->get('database.port'));
    }

    public function testGetWithNonExistentKeyReturnsDefault(): void
    {
        $data = [
            'database' => [
                'name' => 'slim_test',
            ],
        ];

        $config = new Config($data);

        // Test retrieving a non-existent top-level key
        $this->assertNull($config->get('version'));
        $this->assertSame('default', $config->get('version', 'default'));

        // Test retrieving a non-existent nested key
        $this->assertNull($config->get('database.host'));
        $this->assertSame(false, $config->get('database.host', false));
    }

    public function testGetWithEmptyKeyReturnsDefault(): void
    {
        $data = [
            'key1' => 'value1',
        ];

        $config = new Config($data);

        // Test retrieving with an empty key
        $this->assertSame('default', $config->get('', 'default'));
    }

    public function testGetWithPartialNestedKeyReturnsDefault(): void
    {
        $data = [
            'name' => 'Slim',
            'key1' => [
                'displayErrorDetails' => true,
                'key2' => [
                    'key3' => 'debug',
                ],
            ],
        ];

        $config = new Config($data);

        // Test retrieving a partially nested key
        $this->assertSame('debug', $config->get('key1.key2.key3'));
        $this->assertSame('default', $config->get('errors.logErrors.path', 'default'));
    }

    public function testGetWithDeeplyNestedKey(): void
    {
        $data = [
            'parent' => [
                'child' => [
                    'grandchild' => 'value',
                ],
            ],
        ];

        $config = new Config($data);

        // Test retrieving a deeply nested key
        $this->assertSame('value', $config->get('parent.child.grandchild'));
    }
}
