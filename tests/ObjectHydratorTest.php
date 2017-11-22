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
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\ObjectHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeSerializer;

    protected function setUp()
    {
        /** @var DateTimeSerializer $dateTimeSerializer */
        $this->dateTimeSerializer = $dateTimeSerializer = $this->getMockBuilder(DateTimeSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->classNameResolver  = new ClassNameResolver([__NAMESPACE__ . '\\Types']);
        $this->hydrator           = new ObjectHydrator($this->classNameResolver, $dateTimeSerializer);
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
                'photoUrls' => ['/' . (string)rand(), '/' . (string)rand()],
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

        // Just making sure future changes don't introduce crippling performance issues. On very, very, very slow systems this may fail, but unlikely.
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
        $categorySchema = new ObjectSchema((object)[]);
        $categorySchema->setComplexType(new ComplexType('Category', $categorySchema));
        $petSchema = new ObjectSchema(
            (object)[],
            (object)[
                'id'       => new ScalarSchema((object)['type' => 'integer']),
                'price'    => new ScalarSchema((object)['type' => 'number']),
                'category' => $categorySchema,
                'tags'     => new ArraySchema((object)[], $tagSchema),
                'rating'   => new ObjectSchema((object)[], (object)[
                    'value'   => new ScalarSchema((object)['type' => 'number']),
                    'created' => new ScalarSchema((object)['type' => 'string', 'format' => 'date']),
                ]),
            ]
        );
        $petSchema->setComplexType(new ComplexType('Pet', $petSchema));

        return $petSchema;
    }
}
