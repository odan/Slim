<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types = 1);

namespace Slim\Factory\Psr17;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class NyholmPsr17Factory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    private Psr17Factory $psr17Factory;

    private ServerRequestCreator $serverRequestCreator;

    public function __construct(Psr17Factory $psr17Factory, ServerRequestCreator $serverRequestCreator)
    {
        $this->psr17Factory = $psr17Factory;
        $this->serverRequestCreator = $serverRequestCreator;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->psr17Factory->createServerRequest($method, $uri, $serverParams);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return $this->serverRequestCreator->fromGlobals();
    }
}
