<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\ComplexType;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\DateTimeNotParsableException;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\ObjectHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ObjectHydratorTest extends TestCase
{
    /**
     * @var ObjectHydrator
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
        $this->hydrator          = new ObjectHydrator($this->classNameResolver, $dateTimeSerializer);
    }

    /**
     * @test
     */
    public function canHyAndDehydratePet()
    {
        $petSchema = $this->createFullPetSchema();

        $input = (object)[
            'id'        => '1',
            'name'      => 'Fido',
            'status'    => 'single',
            'x'         => 'y',
            'photoUrls' => ['/a', '/b'],
            'price'     => '100.25',
            'category'  => (object)[
                'id'   => '1',
                'name' => 'Shepherd',
            ],
            'tags'      => [
                (object)['id' => '1', 'name' => 1],
                (object)['id' => '2', 'name' => 2],
            ],
            'rating'    => (object)[
                'value'   => '10',
                'created' => '2016-01-01',
            ],
        ];

        $dateTime = new \DateTime();
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($input->rating->created)
            ->willReturn($dateTime);

        /** @var Pet $pet */
        $pet = $this->hydrator->hydrate($input, $petSchema);

        // Making sure the input is unaffected
        $this->assertInternalType('string', $input->rating->created);

        $this->assertInstanceOf(Pet::class, $pet);
        $this->assertInstanceOf(Category::class, $pet->getCategory());
        $this->assertInternalType('int', $pet->getId());
        $this->assertInternalType('array', $pet->getTags());
        $this->assertInstanceOf(Tag::class, $pet->getTags()[0]);
        $this->assertInternalType('string', $pet->getTags()[0]->getName());
        $this->assertInstanceOf(Tag::class, $pet->getTags()[1]);
        $this->assertInternalType('string', $pet->getTags()[1]->getName());
        $this->assertInstanceOf(\stdClass::class, $pet->getRating());
        $this->assertInternalType('int', $pet->getRating()->value);
        $this->assertSame($dateTime, $pet->getRating()->created);

        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('serialize')
            ->with($dateTime)
            ->willReturn($input->rating->created);

        $output = $this->hydrator->dehydrate($pet, $petSchema);

        unset($input->x);
        $this->assertEquals($input, $output);
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
    public function willOmitNullValuesWhenDehydrating()
    {
        $this->assertEquals(
            (object)['foo' => 'a', 'bar'],
            $this->hydrator->dehydrate((object)['foo' => 'a', 'bar', null], new AnySchema())
        );

        $pet = new Pet(1, 'Fido', 'single', 123.12, ['/a', '/b'], new Category(2, 'dogs'), [], (object)[]);

        $refl     = new \ReflectionObject($pet);
        $property = $refl->getProperty('name');
        $property->setAccessible(true);
        $property->setValue($pet, null);

        $petSchema = $this->createFullPetSchema();
        $data      = $this->hydrator->dehydrate($pet, $petSchema);

        $this->assertSame(1, $data->id);
        $this->assertObjectNotHasAttribute('name', $data);
    }

    /**
     * @test
     */
    public function willNotOmitNullTypeValuesWhenDehydrating()
    {
        $object = (object)['aInt' => 1, 'nullProperty' => null];
        $schema = new ObjectSchema((object)[], (object)[
            'aInt' => new ScalarSchema((object)[
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

    /**
     * @test
     */
    public function canHydrateStringUsingAnySchema()
    {
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new DateTimeNotParsableException());

        $this->assertEquals(
            'something',
            $this->hydrator->hydrate('something', new AnySchema())
        );
    }

    /**
     * @test
     */
    public function canHydrateDateTimeUsingAnySchema()
    {
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($expected = new \DateTime());

        $this->assertEquals(
            $expected,
            $this->hydrator->hydrate('something', new AnySchema())
        );
    }

    /**
     * @test
     */
    public function canHydrateNumbersUsingAnySchema()
    {
        $this->assertSame(
            2017,
            $this->hydrator->hydrate('2017', new AnySchema())
        );
        $this->assertSame(
            1.5,
            $this->hydrator->hydrate('1.5', new AnySchema())
        );
    }

    /**
     * @test
     */
    public function willApplyDefaultsWhenHydrating()
    {
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with('now')
            ->willReturn($now = new \DateTime('now'));

        /** @var Pet $actual */
        $actual = $this->hydrator->hydrate(
            (object)[
                'id'     => '1',
                'name'   => 'Fido',
                'rating' => (object)['value' => '10'],
            ],
            $this->createFullPetSchema()
        );

        $this->assertInstanceOf(Pet::class, $actual);
        $this->assertSame(100.0, $actual->getPrice());
        $this->assertSame([], $actual->getTags());
        $this->assertSame($now, $actual->getRating()->created);

        $this->expectException(\TypeError::class);
        $actual->getPhotoUrls();
    }

    /**
     * @test
     * @group perf
     */
    public function canHandleLargeArray()
    {
        $start = microtime(true);

        $size = 10000;
        $this->dateTimeSerializer
            ->expects($this->any())
            ->method('deserialize')
            ->willReturnCallback(function ($value) {
                return new\DateTime($value);
            });

        $input = [];

        for ($i = 0; $i < $size; ++$i) {
            $input[] = (object)[
                'id'        => (string)rand(),
                'name'      => (string)rand(),
                'status'    => (string)rand(),
                'x'         => 'y',
                'photoUrls' => [' / ' . (string)rand(), ' / ' . (string)rand()],
                'price'     => (string)rand() . '.25',
                'category'  => (object)[
                    'name' => 'Shepherd',
                ],
                'tags'      => [
                    (object)['name' => (string)rand()],
                    (object)['name' => (string)rand()],
                ],
                'rating'    => (object)[
                    'value'   => '10',
                    'created' => '2016-01-01',
                ],
            ];
        }
        $this->hydrator->hydrate($input, new ArraySchema((object)[], $this->createFullPetSchema()));

        // Just making sure future changes don't introduce crippling performance issues .
        // This runs in under 2s on my old W3570. Travis does it in about 3.7s at the time of writing.
        $this->assertLessThan(5, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function willThrowExceptionIfTryingToHydrateInt64On32BitOs()
    {
        $this->expectException(UnsupportedException::class);

        $petSchema = new ScalarSchema((object)['type' => 'integer', 'format' => Schema::FORMAT_INT64]);

        /** @var DateTimeSerializer $dateTimeSerializer */
        $hydrator = new ObjectHydrator($this->classNameResolver, $dateTimeSerializer = $this->dateTimeSerializer, true);
        $hydrator->hydrate(['id' => 1], $petSchema);
    }

    /**
     * @return ObjectSchema
     */
    private function createFullPetSchema(): ObjectSchema
    {
        $tagSchema = new ObjectSchema((object)[], (object)[
            'name' => new ScalarSchema((object)['type' => 'string']),
        ]);
        $tagSchema->setComplexType(new ComplexType('Tag', $tagSchema));
        $categorySchema = new ObjectSchema((object)[], (object)[]);
        $categorySchema->setComplexType(new ComplexType('Category', $categorySchema));
        $petSchema = new ObjectSchema(
            (object)[],
            (object)[
                'id'       => new ScalarSchema((object)['type' => 'integer']),
                'price'    => new ScalarSchema((object)['type' => 'number', 'default' => 100.0]),
                'label'    => new ScalarSchema((object)['type' => 'string']),
                'category' => $categorySchema,
                'tags'     => new ArraySchema((object)['default' => []], $tagSchema),
                'rating'   => new ObjectSchema((object)[], (object)[
                    'value'   => new ScalarSchema((object)['type' => 'number']),
                    'created' => new ScalarSchema((object)[
                        'type'    => 'string',
                        'format'  => 'date',
                        'default' => 'now',
                    ]),
                ]),
            ]
        );
        $petSchema->setComplexType(new ComplexType('Pet', $petSchema));

        return $petSchema;
    }
}
