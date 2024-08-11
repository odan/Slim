<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

interface ContainerResolverInterface
{
    /**
     * Resolve the given $identifier to an object.
     *
     * @param callable|object|array|string $identifier
     *
     * @return mixed An object or callable
     */
    public function resolve(callable|object|array|string $identifier): mixed;

    /**
     * Resolve the given $identifier to an object or callable.
     *
     * @param callable|string|array $identifier
     *
     * @return callable A callable
     */
    public function resolveCallable(callable|array|string $identifier): callable;

    /**
     * Resolve the given $identifier to a callable that is bounded to the container.
     *
     * @param callable|string|array $identifier
     *
     * @return callable A callable
     */
    public function resolveRoute(callable|array|string $identifier): callable;
}
