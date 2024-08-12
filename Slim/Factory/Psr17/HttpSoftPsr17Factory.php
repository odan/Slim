<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\ServerRequest\ServerRequestCreator;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class HttpSoftPsr17Factory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    private ServerRequestFactory $serverRequestFactory;

    private ServerRequestCreator $serverRequestCreator;

    public function __construct(ServerRequestFactory $serverRequestFactory, ServerRequestCreator $serverRequestCreator)
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->serverRequestCreator = $serverRequestCreator;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return $this->serverRequestCreator->createFromGlobals();
    }
}
