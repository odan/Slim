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

    private UrlGenerator $urlGenerator;

    private RoutingResults $routingResults;

    private ?string $basePath;

    private function __construct(
        UrlGenerator $urlGenerator,
        RoutingResults $routingResults,
        ?string $basePath = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $urlGenerator = $request->getAttribute(self::URL_GENERATOR);
        $routingResults = $request->getAttribute(self::ROUTING_RESULTS);
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

        /** @var UrlGenerator $urlGenerator */
        /** @var RoutingResults $routingResults */
        /** @var string|null $basePath */
        return new self($urlGenerator, $routingResults, $basePath);
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
}
