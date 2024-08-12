<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Builder;

use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container\DefaultDefinitions;
use Slim\Container\MiddlewareResolver;
use Slim\Enums\MiddlewareOrder;
use Slim\Interfaces\ContainerResolverInterface;

/**
 * This class is responsible for building and configuring a Slim application with a dependency injection (DI) container.
 * It provides methods to set up service definitions, configure a custom container factory, and more.
 *
 * Key functionalities include:
 * - Building the Slim `App` instance with configured dependencies.
 * - Customizing the DI container with user-defined service definitions or a custom container factory.
 * - Setting up middleware in a specified order.
 * - Configuring application settings.
 */
final class AppBuilder
{
    private array $definitions = [];

    /**
     * @var callable|null
     */
    private $containerFactory = null;

    public function __construct()
    {
        $this->setDefinitions(new DefaultDefinitions());
    }

    // Set up Slim with the DI container
    public function build(): App
    {
        return $this->buildContainer()->get(App::class);
    }

    // Create the container
    private function buildContainer(): ContainerInterface
    {
        return $this->containerFactory
            ? call_user_func($this->containerFactory, $this->definitions)
            : new Container($this->definitions);
    }

    public function setDefinitions(array|callable $definitions): self
    {
        if (is_callable($definitions)) {
            $definitions = (array)$definitions();
        }
        $this->definitions = array_merge($this->definitions, $definitions);

        return $this;
    }

    public function setContainerFactory(callable $factory): self
    {
        $this->containerFactory = $factory;

        return $this;
    }

    public function setMiddlewareOrder(MiddlewareOrder $order): self
    {
        $this->setDefinitions(
            [
                MiddlewareResolver::class => function (ContainerInterface $container) use ($order) {
                    return new MiddlewareResolver(
                        $container,
                        $container->get(ContainerResolverInterface::class),
                        $order
                    );
                },
            ]
        );

        return $this;
    }

    public function setSettings(array $settings): self
    {
        $this->setDefinitions(
            [
                'settings' => $settings,
            ]
        );

        return $this;
    }
}
