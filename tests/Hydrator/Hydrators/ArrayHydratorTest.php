<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ArrayHydrator;
use PHPUnit\Framework\TestCase;

class ArrayHydratorTest extends TestCase
{
    /**
     * @var ArrayHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new ArrayHydrator();
    }

    /**
     * @test
     */
    public function supportsArrays()
    {
        $this->assertTrue($this->hydrator->supports([], new AnySchema()));
    }

    /**
     * @test
     */
    public function willBubbleValues()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Hydrator::class);
        $this->hydrator->setParent($parent);

        $schema = new AnySchema();

        $mockParent
            ->expects($this->exactly(3))
            ->method('hydrate')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema]);

        $mockParent
            ->expects($this->exactly(3))
            ->method('supports')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema])
            ->willReturn(true);

        $this->hydrator->hydrate([3, 2, 1], $schema);
    }

    /**
     * @test
     */
    public function willAssembleOutputArray()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Hydrator::class);
        $this->hydrator->setParent($parent);

        $schema = new AnySchema();

        $mockParent
            ->expects($this->exactly(3))
            ->method('hydrate')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->hydrator->hydrate([3, 2, 1], $schema);

        $this->assertSame(['three', 'two', 'one'], $actual);
    }

    /**
     * @test
     */
    public function willUtilizeItemsSchema()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Hydrator::class);
        $this->hydrator->setParent($parent);

        $schema = new ArraySchema((object)[], $itemsSchema = new ScalarSchema((object)[]));

        $mockParent
            ->expects($this->exactly(3))
            ->method('hydrate')
            ->withConsecutive([3, $itemsSchema], [2, $itemsSchema], [1, $itemsSchema])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->hydrator->hydrate([3, 2, 1], $schema);

        $this->assertSame(['three', 'two', 'one'], $actual);
    }
}
