<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\MiddlewareCollection;

interface MiddlewareCollectionInterface
{
    // public function getMiddlewareStack(): MiddlewareCollection;

    public function add(MiddlewareInterface|callable|string $middleware): self;

    public function addMiddleware(MiddlewareInterface $middleware): self;
}
