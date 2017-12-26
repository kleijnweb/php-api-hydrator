<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\DefaultCompositeDehydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class SchemaNodeDehydratorTest extends TestCase
{
    /**
     * @var DefaultCompositeDehydrator
     */
    private $hydrator;

    /**
     * @var ClassNameResolver
     */
    private $classNameResolver;

    /**
     * @var MockObject
     */
    private $dateTimeSerializer;

    protected function setUp()
    {
        /** @var DateTimeSerializer $dateTimeSerializer */
        $this->dateTimeSerializer = $dateTimeSerializer = $this->getMockBuilder(DateTimeSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->classNameResolver = new ClassNameResolver([__NAMESPACE__ . '\\Types']);
        $this->hydrator          = new DefaultCompositeDehydrator($dateTimeSerializer);
    }

    /**
     * @test
     */
    public function canDehydratePet()
    {
        $petSchema = TestSchemaFactory::createFullPetSchema();

        $expected = (object)[
            'id'        => 1,
            'name'      => 'Fido',
            'status'    => 'single',
            'photoUrls' => ['/a', '/b'],
            'price'     => 100.25,
            'category'  => (object)[
                'id'   => 1,
                'name' => 'Shepherd',
            ],
            'tags'      => [
                (object)['id' => 1, 'name' => '1'],
                (object)['id' => 2, 'name' => '2'],
            ],
            'rating'    => (object)[
                'value'   => '10',
                'created' => '2016-01-01',
            ],
        ];

        $pet = new Pet(
            $expected->id,
            $expected->name,
            $expected->status,
            $expected->price,
            $expected->photoUrls,
            new Category(
                $expected->category->id,
                $expected->category->name
            ),
            [
                new Tag(
                    $expected->tags[0]->id,
                    $expected->tags[0]->name
                ),
                new Tag(
                    $expected->tags[1]->id,
                    $expected->tags[1]->name
                ),
            ],
            (object)[
                'value'   => $expected->rating->value,
                'created' => new \DateTime($expected->rating->created),
            ]
        );

        // Intentional miscasting of types
        $this->forceProperty($pet, 'id', (string)$pet->getId());
        $this->forceProperty($pet, 'price', (float)$pet->getPrice());
        $this->forceProperty($category = $pet->getCategory(), 'id', (string)$category->getId());
        $this->forceProperty($tag = $pet->getTags()[1], 'id', (string)$tag->getId());
        $this->forceProperty($tag = $pet->getTags()[1], 'name', (int)$tag->getName());

        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturn($expected->rating->created);

        $this->assertEquals($expected, $this->hydrator->dehydrate($pet, $petSchema));
    }

    /**
     * @test
     */
    public function canDehydratePetWithAnySchema()
    {
        $dateTime       = new \DateTime('2016-01-01');
        $serializedDate = 'faux date-time';
        $tags           = [
            new Tag(1, 'one'),
            new Tag(2, 'two'),
        ];
        $pet            = new Pet(1, 'Fido', 'single', 123.12, ['/a', '/b'], new Category(2, 'dogs'), $tags, (object)[
            'value'   => 10,
            'created' => $dateTime,
        ]);

        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('serialize')
            ->with($dateTime)
            ->willReturn($serializedDate);

        /** @var Pet $pet */
        $petAnonObject = $this->hydrator->dehydrate($pet, new AnySchema());

        $this->assertInstanceOf(\stdClass::class, $petAnonObject);
        $this->assertSame(1, $petAnonObject->id);
        $this->assertSame($pet->getPhotoUrls(), $petAnonObject->photoUrls);
        $this->assertSame($pet->getCategory()->getName(), $petAnonObject->category->name);
        $this->assertSame($pet->getTags()[0]->getName(), $petAnonObject->tags[0]->name);
        $this->assertSame($pet->getTags()[1]->getName(), $petAnonObject->tags[1]->name);
        $this->assertSame($pet->getRating()->value, $petAnonObject->rating->value);
        $this->assertSame($serializedDate, $petAnonObject->rating->created);
    }

    /**
     * @test
     */
    public function willOmitNullValuesOnTypedObjectsWhenDehydrating()
    {
        $pet = new Pet(1, 'Fido', 'single', 123.12, ['/a', '/b'], new Category(2, 'dogs'), [], (object)[]);

        $refl     = new \ReflectionObject($pet);
        $property = $refl->getProperty('name');
        $property->setAccessible(true);
        $property->setValue($pet, null);

        $petSchema = TestSchemaFactory::createFullPetSchema();
        $data      = $this->hydrator->dehydrate($pet, $petSchema);

        $this->assertSame(1, $data->id);
        $this->assertObjectNotHasAttribute('name', $data);
    }

    /**
     * @test
     */
    public function willNotOmitNullValuesOnUnTypedObjectsWhenDehydrating()
    {
        $object = (object)['aInt' => 1, 'nullProperty' => null];
        $schema = new ObjectSchema((object)[], (object)[
            'aInt'         => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
            'nullProperty' => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
        ]);

        $data = $this->hydrator->dehydrate($object, $schema);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    /**
     * @test
     */
    public function willNotOmitNullTypeValuesOnTypedObjectsWhenDehydrating()
    {
        $object = (object)['aInt' => 1, 'nullProperty' => null];
        $schema = new ObjectSchema((object)[], (object)[
            'aInt'         => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_INT,
            ]),
            'nullProperty' => new ScalarSchema((object)[
                'type' => ScalarSchema::TYPE_NULL,
            ]),
        ]);

        $data = $this->hydrator->dehydrate($object, $schema);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    private function forceProperty($object, $propertyName, $value)
    {
        $reflector = new \ReflectionObject($object);
        $attribute = $reflector->getProperty($propertyName);
        $attribute->setAccessible(true);
        $attribute->setValue($object, $value);
    }
}
