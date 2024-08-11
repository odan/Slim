<?php

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\UrlGenerator;

/**
 * Middleware that injects the URL generator into the request attributes.
 *
 * This allows the URL generator to be accessed in a route callable.
 */
final class UrlGeneratorMiddleware implements MiddlewareInterface
{
    private UrlGenerator $urlGenerator;

    public function __construct(UrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(RouteContext::URL_GENERATOR, $this->urlGenerator));
    }
}
