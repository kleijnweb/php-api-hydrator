<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\ArrayDehydrator;
use PHPUnit\Framework\TestCase;

class ArrayDehydratorTest extends TestCase
{
    /**
     * @var ArrayDehydrator
     */
    private $dehydrator;

    protected function setUp()
    {
        $this->dehydrator = new ArrayDehydrator();
    }

    /**
     * @test
     */
    public function supportsArrays()
    {
        $this->assertTrue($this->dehydrator->supports([], new AnySchema()));
    }

    /**
     * @test
     */
    public function willBubbleValues()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $schema = new AnySchema();

        $mockParent
            ->expects($this->exactly(3))
            ->method('dehydrate')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema]);

        $mockParent
            ->expects($this->exactly(3))
            ->method('supports')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema])
            ->willReturn(true);

        $this->dehydrator->dehydrate([3, 2, 1], $schema);
    }

    /**
     * @test
     */
    public function willAssembleOutputArray()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $schema = new AnySchema();

        $mockParent
            ->expects($this->exactly(3))
            ->method('dehydrate')
            ->withConsecutive([3, $schema], [2, $schema], [1, $schema])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->dehydrator->dehydrate([3, 2, 1], $schema);

        $this->assertSame(['three', 'two', 'one'], $actual);
    }

    /**
     * @test
     */
    public function willUtilizeItemsSchema()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $schema = new ArraySchema((object)[], $itemsSchema = new ScalarSchema((object)[]));

        $mockParent
            ->expects($this->exactly(3))
            ->method('dehydrate')
            ->withConsecutive([3, $itemsSchema], [2, $itemsSchema], [1, $itemsSchema])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->dehydrator->dehydrate([3, 2, 1], $schema);

        $this->assertSame(['three', 'two', 'one'], $actual);
    }
}
