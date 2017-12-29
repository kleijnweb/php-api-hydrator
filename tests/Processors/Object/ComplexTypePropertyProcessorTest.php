<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Object;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Processors\Object\ComplexTypePropertyProcessor;
use KleijnWeb\PhpApi\Hydrator\Tests\TestHelperFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;

class ComplexTypePropertyProcessorTest extends ObjectProcessorTest
{
    /**
     * @test
     */
    public function canHydrateTypedObject()
    {
        $processor = $this->createProcessor(function (ObjectSchema $schema) {
            return $this->factory($schema, Tag::class);
        }, TestHelperFactory::createTagSchema());

        $this->mockPropertyProcesser
            ->expects($this->once())
            ->method('hydrate')
            ->with(2)
            ->willReturn(999);

        /** @var Tag $actual */
        $actual = $processor->hydrate((object)['id' => 2]);

        $this->assertInstanceOf(Tag::class, $actual);
        $this->assertSame(999, $actual->getId());
    }

    /**
     *
     * @test
     */
    public function willOmitNullValuesOnTypedObjectsWhenDehydrating()
    {
        $processor = $this->createProcessor(function (ObjectSchema $schema) {
            return $this->factory($schema, Pet::class);
        }, 'id', 'name', 'status');

        $this->mockPropertyProcesser
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $pet = new Pet(1, 'Fido', 'single', 123.12, ['/a', '/b'], new Category(2, 'dogs'), [], (object)[]);

        $refl     = new \ReflectionObject($pet);
        $property = $refl->getProperty('name');
        $property->setAccessible(true);
        $property->setValue($pet, null);

        $data = $processor->dehydrate($pet);

        $this->assertSame(1, $data->id);
        $this->assertObjectNotHasAttribute('name', $data);
    }

    /**
     * @test
     */
    public function willNotOmitNullTypeValuesOnTypedObjectsWhenDehydrating()
    {
        $processor = $this->createProcessor(
            function (ObjectSchema $schema) {
                $className = get_class(new class
                {
                    private $aInt;
                    private $nullProperty;
                });

                return $this->factory($schema, $className);
            },
            (object)[
                'aInt'         => new ScalarSchema((object)[
                    'type' => ScalarSchema::TYPE_INT,
                ]),
                'nullProperty' => new ScalarSchema((object)[
                    'type' => ScalarSchema::TYPE_NULL,
                ]),
            ]);

        $this->mockPropertyProcesser
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $object = (object)['aInt' => 1, 'nullProperty' => null];

        $data = $processor->dehydrate($object);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    protected function factory(ObjectSchema $schema, string $className): ComplexTypePropertyProcessor
    {
        return new ComplexTypePropertyProcessor($schema, $className);
    }
}
