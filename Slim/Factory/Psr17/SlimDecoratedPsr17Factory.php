<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\ServerRequest;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SlimDecoratedPsr17Factory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    private ServerRequestFactory $serverRequestFactory;

    public function __construct(ServerRequestFactory $serverRequestFactory)
    {
        $this->serverRequestFactory = $serverRequestFactory;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($this->serverRequestFactory->createServerRequest($method, $uri, $serverParams));
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return new ServerRequest($this->serverRequestFactory->createFromGlobals());
    }
}
