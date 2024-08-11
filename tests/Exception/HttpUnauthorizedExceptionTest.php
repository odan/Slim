<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Tests\Traits\AppTestTrait;

final class HttpUnauthorizedExceptionTest extends TestCase
{
    use AppTestTrait;

    public function setUp(): void
    {
        $this->setUpApp();
    }

    public function testHttpUnauthorizedException()
    {
        $request = $this->createServerRequest('GET', '/');
        $exception = new HttpUnauthorizedException($request);

        $this->assertInstanceOf(HttpUnauthorizedException::class, $exception);
    }

    public function testHttpUnauthorizedExceptionWithMessage()
    {
        $request = $this->createServerRequest('GET', '/');
        $exception = new HttpUnauthorizedException($request, 'Hello World');

        $this->assertSame('Hello World', $exception->getMessage());
    }
}
