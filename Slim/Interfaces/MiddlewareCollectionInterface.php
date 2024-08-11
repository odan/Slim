<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareCollectionInterface
{
    public function getMiddlewareStack(): array;

    public function add(MiddlewareInterface|callable|string|array $middleware): self;

    public function addMiddleware(MiddlewareInterface|callable|string|array $middleware): self;
}
