<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\SimpleObjectHydrator;
use PHPUnit\Framework\TestCase;

class SimpleObjectHydratorTest extends TestCase
{
    /**
     * @var SimpleObjectHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new SimpleObjectHydrator();
    }

    /**
     * @test
     */
    public function supportsSimpleObjects()
    {
        $this->assertFalse($this->hydrator->supports([], new AnySchema()));
        $this->assertFalse($this->hydrator->supports('', new AnySchema()));
        $this->assertFalse($this->hydrator->supports($this, new AnySchema()));
        $this->assertTrue($this->hydrator->supports((object)[], new AnySchema()));
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

        $this->hydrator->hydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);
    }

    /**
     * @test
     */
    public function willAssembleOutputObject()
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

        $actual = $this->hydrator->hydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }

    /**
     * @test
     */
    public function willUtilizePropertySchema()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Hydrator::class);
        $this->hydrator->setParent($parent);

        $schema = new ObjectSchema(
            (object)[],
            $propertiesSchemas = (object)[
                'a' => new ScalarSchema((object)[]),
                'b' => new ScalarSchema((object)[]),
                'c' => new ScalarSchema((object)[]),
            ]

        );

        $mockParent
            ->expects($this->exactly(3))
            ->method('hydrate')
            ->withConsecutive([3, $propertiesSchemas->a], [2, $propertiesSchemas->b], [1, $propertiesSchemas->c])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->hydrator->hydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }
}
