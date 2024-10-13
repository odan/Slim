<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareCollectionInterface
{
    public function add(MiddlewareInterface|callable|string $middleware): self;

    public function addMiddleware(MiddlewareInterface $middleware): self;
}
