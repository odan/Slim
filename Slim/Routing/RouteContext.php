<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RouteContext
{
    public const URL_GENERATOR = '__urlGenerator__';

    public const ROUTING_RESULTS = '__routingResults__';

    public const BASE_PATH = '__basePath__';

    private RoutingResults $routingResults;

    private UrlGenerator $urlGenerator;

    private ?string $basePath;

    private function __construct(
        RoutingResults $routingResults,
        UrlGenerator $urlGenerator,
        ?string $basePath = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        /* @var UrlGenerator|null $urlGenerator */
        $urlGenerator = $request->getAttribute(self::URL_GENERATOR);

        /* @var RoutingResults|null $routingResults */
        $routingResults = $request->getAttribute(self::ROUTING_RESULTS);

        /* @var string|null $basePath */
        $basePath = $request->getAttribute(self::BASE_PATH);

        if ($urlGenerator === null) {
            throw new RuntimeException(
                'Cannot create RouteContext before routing has been completed. Add UrlGeneratorMiddleware to fix this.'
            );
        }

        if ($routingResults === null) {
            throw new RuntimeException(
                'Cannot create RouteContext before routing has been completed. Add RoutingMiddleware to fix this.'
            );
        }

        return new self($routingResults, $urlGenerator, $basePath);
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return $this->urlGenerator;
    }

    public function getRoutingResults(): RoutingResults
    {
        return $this->routingResults;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function getRoute(): ?Route
    {
        return $this->routingResults->getRoute();
    }

    public function getArguments(): array
    {
        return $this->routingResults->getRouteArguments();
    }

    public function getArgument(string $key): mixed
    {
        return $this->routingResults->getRouteArgument($key);
    }
}
