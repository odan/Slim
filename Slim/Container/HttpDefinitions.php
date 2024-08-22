<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use GuzzleHttp\Psr7\ServerRequest;
use HttpSoft\Message\RequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use RuntimeException;
use Slim\Http\Factory\DecoratedServerRequestFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class HttpDefinitions
{
    private array $classes = [
        DecoratedServerRequestFactory::class => SlimHttpDefinitions::class,
        ServerRequestFactory::class => SlimPsr7Definitions::class,
        Psr17Factory::class => NyholmDefinitions::class,
        ServerRequest::class => GuzzleDefinitions::class,
        RequestFactory::class => HttpSoftDefinitions::class,
    ];

    public function __invoke(): array
    {
        foreach ($this->classes as $factory => $definitionClass) {
            if (class_exists($factory)) {
                return call_user_func(new $definitionClass());
            }
        }

        throw new RuntimeException(
            'Could not detect any PSR-17 ResponseFactory implementations. ' .
            'Please install a supported implementation. ' .
            'See https://github.com/slimphp/Slim/blob/5.x/README.md for a list of supported implementations.'
        );
    }
}
