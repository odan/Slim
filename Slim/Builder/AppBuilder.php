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
    /**
     * @var array Service definitions for the DI container
     */
    private array $definitions = [];

    /**
     * @var callable|null Factory function for creating a custom DI container
     */
    private $containerFactory = null;

    /**
     * The constructor.
     *
     * Initializes the builder with the default service definitions.
     */
    public function __construct()
    {
        $this->setDefinitions(DefaultDefinitions::class);
    }

    /**
     * Builds the Slim application instance using the configured DI container.
     *
     * @return App The fully built Slim application instance
     */
    public function build(): App
    {
        return $this->buildContainer()->get(App::class);
    }

    /**
     * Creates and configures the DI container.
     *
     * If a custom container factory is set, it will be used to create the container;
     * otherwise, a default container with the provided definitions will be created.
     *
     * @return ContainerInterface The configured DI container
     */
    private function buildContainer(): ContainerInterface
    {
        return $this->containerFactory
            ? call_user_func($this->containerFactory, $this->definitions)
            : new Container($this->definitions);
    }

    /**
     * Sets the service definitions for the DI container.
     *
     * The method accepts either an array of definitions or the name of a class that provides definitions.
     * If a class name is provided, its definitions are added to the existing ones.
     *
     * @param array|string $definitions An array of service definitions or a class name providing them
     *
     * @return self The current AppBuilder instance for method chaining
     */
    public function setDefinitions(array|string $definitions): self
    {
        if (is_string($definitions)) {
            $definitions = (array)call_user_func(new $definitions());
        }

        $this->definitions = array_merge($this->definitions, $definitions);

        return $this;
    }

    /**
     * Sets a custom factory for creating the DI container.
     *
     * @param callable $factory A callable that returns a configured DI container
     *
     * @return self The current AppBuilder instance for method chaining
     */
    public function setContainerFactory(callable $factory): self
    {
        $this->containerFactory = $factory;

        return $this;
    }

    /**
     * Configures the order of middleware execution in the application.
     *
     * This method sets up a MiddlewareResolver with the specified order of middleware.
     *
     * @param MiddlewareOrder $order The desired order of middleware execution
     *
     * @return self The current AppBuilder instance for method chaining
     */
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

    /**
     * Sets application-wide settings in the DI container.
     *
     * This method allows the user to configure various settings for the Slim application,
     * such as display_error_details, log_error_details, etc., by passing an associative array of settings.
     *
     * @param array $settings An associative array of application settings
     *
     * @return self The current AppBuilder instance for method chaining
     */
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
