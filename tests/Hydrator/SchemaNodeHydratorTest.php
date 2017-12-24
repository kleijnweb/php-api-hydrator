<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\DateTimeNotParsableException;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\DefaultCompositeHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class SchemaNodeHydratorTest extends TestCase
{
    /**
     * @var DefaultCompositeHydrator
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

        $this->hydrator = new DefaultCompositeHydrator(
            $this->classNameResolver = new ClassNameResolver(['KleijnWeb\PhpApi\Hydrator\Tests\Types'])
            , $dateTimeSerializer);
    }

    /**
     * @test
     */
    public function canHydratePet()
    {
        $petSchema = TestSchemaFactory::createFullPetSchema();

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
    public function canHydrateCompositesUsingAnySchema()
    {
        $this->assertEquals(
            [(object)['a' => 1]],
            $this->hydrator->hydrate([(object)['a' => 1]], new AnySchema())
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
            TestSchemaFactory::createFullPetSchema()
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
    public function canHydrateLargeArray()
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
        $this->hydrator->hydrate($input, new ArraySchema((object)[], TestSchemaFactory::createFullPetSchema()));

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

        $schema = new ScalarSchema((object)['type' => 'integer', 'format' => Schema::FORMAT_INT64]);

        /** @var DateTimeSerializer $dateTimeSerializer */
        $hydrator = new DefaultCompositeHydrator($this->classNameResolver, $this->dateTimeSerializer, true);
        $hydrator->hydrate(1, $schema);
    }
}
