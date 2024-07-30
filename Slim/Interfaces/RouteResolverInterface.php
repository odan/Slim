<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Routing\RoutingResults;

interface RouteResolverInterface
{
    /**
     * @param string $uri Should be ServerRequestInterface::getUri()->getPath()
     * @param string $method
     */
    public function computeRoutingResults(string $uri, string $method): RoutingResults;

    public function resolveRoute(string $identifier): RouteInterface;
}
