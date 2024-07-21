<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory\Psr17;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Tests\TestCase;

class Psr17FactoryProviderTest extends TestCase
{
    /**
     * RunInSeparateProcess - Psr17FactoryProvider::setFactories breaks other tests
     */
    #[RunInSeparateProcess()]
    public function testGetSetFactories()
    {
        Psr17FactoryProvider::setFactories([]);

        $this->assertSame([], Psr17FactoryProvider::getFactories());
    }


    /**
     * RunInSeparateProcess - Psr17FactoryProvider::setFactories breaks other tests
     */
    #[RunInSeparateProcess()]
    public function testAddFactory()
    {
        Psr17FactoryProvider::setFactories(['Factory 1']);
        Psr17FactoryProvider::addFactory('Factory 2');

        $this->assertSame(['Factory 2', 'Factory 1'], Psr17FactoryProvider::getFactories());
    }
}
