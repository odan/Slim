<?php

namespace Slim\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Formatting\ContentNegotiationResult;

interface ContentNegotiatorInterface
{
    public function negotiate(ServerRequestInterface $request): ContentNegotiationResult;

    public function setHandler(string $mediaType, callable|string $handler): self;
}
