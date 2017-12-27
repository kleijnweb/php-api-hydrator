<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\ComplexObjectIteratorFactory;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\ObjectDehydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\TestCase;

class ObjectDehydratorTest extends TestCase
{
    /**
     * @var ObjectDehydrator
     */
    private $dehydrator;

    protected function setUp()
    {
        $this->dehydrator = new ObjectDehydrator();
    }

    /**
     * @test
     */
    public function supportsObjects()
    {
        $this->assertFalse($this->dehydrator->supports([], new AnySchema()));
        $this->assertFalse($this->dehydrator->supports('', new AnySchema()));
        $this->assertTrue($this->dehydrator->supports((object)[], new AnySchema()));
        $this->assertTrue($this->dehydrator->supports($this, new AnySchema()));
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

        $this->dehydrator->dehydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);
    }


    /**
     * @test
     */
    public function willAssembleOutputObject()
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

        $actual = $this->dehydrator->dehydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }

    /**
     * @test
     */
    public function willUtilizePropertySchema()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

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
            ->method('dehydrate')
            ->withConsecutive([3, $propertiesSchemas->a], [2, $propertiesSchemas->b], [1, $propertiesSchemas->c])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $actual = $this->dehydrator->dehydrate((object)['a' => 3, 'b' => 2, 'c' => 1], $schema);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }

    /**
     * @test
     */
    public function willCreateIteratorForComplexObjects()
    {
        /** @var ComplexObjectIteratorFactory $iteratorFactory */
        $iteratorFactory = $mockFactory = $this
            ->getMockBuilder(ComplexObjectIteratorFactory::class)
            ->disableOriginalConstructor()->getMock();

        $dehydrator = new ObjectDehydrator($iteratorFactory);
        $tag        = new Tag(1, 'a');

        $mockFactory
            ->expects($this->once())
            ->method('create')
            ->with($tag);

        $schema = new ObjectSchema((object)[], (object)[]);

        $dehydrator->dehydrate(new Tag(1, 'a'), $schema);
    }


    /**
     * @test
     */
    public function willNotOmitNullValuesOnUnTypedObjectsWhenDehydrating()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $object = (object)['aInt' => 1, 'nullProperty' => null];
        $schema = new ObjectSchema((object)[], $propertiesSchemas = (object)[
            'aInt'         => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
            'nullProperty' => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
        ]);

        $mockParent
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $data = $this->dehydrator->dehydrate($object, $schema);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    /**
     * @test
     */
    public function willNotOmitNullTypeValuesOnTypedObjectsWhenDehydrating()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $mockParent
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $object = (object)['aInt' => 1, 'nullProperty' => null];
        $schema = new ObjectSchema((object)[], (object)[
            'aInt'         => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
            'nullProperty' => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_NULL,
            ]),
        ]);

        $data = $this->dehydrator->dehydrate($object, $schema);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    /**
     * @test
     */
    public function willOmitNullValuesOnTypedObjectsWhenDehydrating()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        $mockParent
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $mockParent
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $pet = new Pet(1, 'Fido', 'single', 123.12, ['/a', '/b'], new Category(2, 'dogs'), [], (object)[]);

        $refl     = new \ReflectionObject($pet);
        $property = $refl->getProperty('name');
        $property->setAccessible(true);
        $property->setValue($pet, null);

        $petSchema = TestSchemaFactory::createFullPetSchema();
        $data      = $this->dehydrator->dehydrate($pet, $petSchema);

        $this->assertSame(1, $data->id);
        $this->assertObjectNotHasAttribute('name', $data);
    }
}
