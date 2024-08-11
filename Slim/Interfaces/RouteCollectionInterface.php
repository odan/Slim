<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Routing\Route;
use Slim\Routing\RouteGroup;

interface RouteCollectionInterface
{
    public function map(array $methods, string $path, callable|string $handler): Route;

    public function group(string $path, callable $handler): RouteGroup;

    public function any(string $path, callable|string $handler): Route;

    public function delete(string $path, callable|string $handler): Route;

    public function get(string $path, callable|string $handler): Route;

    public function head(string $path, callable|string $handler): Route;

    public function options(string $path, callable|string $handler): Route;

    public function patch(string $path, callable|string $handler): Route;

    public function post(string $path, callable|string $handler): Route;

    public function put(string $path, callable|string $handler): Route;
}
