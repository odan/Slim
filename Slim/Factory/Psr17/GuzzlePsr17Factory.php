<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class GuzzlePsr17Factory implements ServerRequestFactoryInterface, ServerRequestCreatorInterface
{
    private ServerRequest $serverRequest;

    private HttpFactory $httpFactory;

    public function __construct(HttpFactory $httpFactory, ServerRequest $serverRequest)
    {
        $this->httpFactory = $httpFactory;
        $this->serverRequest = $serverRequest;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->httpFactory->createServerRequest($method, $uri, $serverParams);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        return $this->serverRequest->fromGlobals();
    }
}
