<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
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

        $this->classNameResolver = new ClassNameResolver(['KleijnWeb\PhpApi\Hydrator\Tests\Types']);
        $this->hydrator          = new ObjectHydrator($this->classNameResolver, $dateTimeSerializer);
    }

    /**
     * @test
     */
    public function canRoundTrip()
    {
        $petSchema = TestSchemaFactory::createPetSchema();

        $input = (object)[
            'id'        => 1,
            'name'      => 'Fido',
            'status'    => 'single',
            'x'         => 'y',
            'photoUrls' => ['/a', '/b'],
            'price'     => 100.25,
            'category'  => (object)[
                'id'   => '1',
                'name' => 'Shepherd',
            ],
            'tags'      => [
                (object)['id' => 1, 'name' => '1'],
                (object)['id' => 2, 'name' => '2'],
            ],
            'rating'    => (object)[
                'value'   => 10,
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
        $this->assertInternalType('int', $input->rating->value);

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

}
