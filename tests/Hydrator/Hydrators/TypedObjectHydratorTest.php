<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\ComplexType;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\TypedObjectPropertyHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\TestCase;

class TypedObjectHydratorTest extends TestCase
{
    /**
     * @var TypedObjectPropertyHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new TypedObjectPropertyHydrator(
            new ClassNameResolver(['KleijnWeb\PhpApi\Hydrator\Tests\Types'])
        );
    }

    /**
     * @test
     */
    public function supportsObjectsWithComplexTypes()
    {
        $this->assertFalse($this->hydrator->supports((object)[], new AnySchema()));
        $this->assertFalse($this->hydrator->supports((object)[], new ObjectSchema((object)[])));

        $schema = new ObjectSchema((object)[], (object)[]);
        $schema->setComplexType(new ComplexType('SomeType', $schema));

        $this->assertTrue($this->hydrator->supports((object)[], $schema));
    }

    /**
     * @test
     */
    public function canHydrateTypedObject()
    {
        /** @var Tag $actual */
        $actual = $this->hydrator->hydrate(
            (object)['id' => 2],
            TestSchemaFactory::createTagSchema()
        );

        $this->assertInstanceOf(Tag::class, $actual);
        $this->assertSame(2, $actual->getId());
    }

    /**
     * @test
     */
    public function willBubble()
    {
        /** @var Hydrator $parent */
        $parent = $stubHydrator = $this->getMockBuilder(Hydrator::class)->getMockForAbstractClass();
        $stubHydrator
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $stubHydrator
            ->expects($this->any())
            ->method('hydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $this->hydrator->setParent($parent);

        /** @var Pet $actual */
        $actual = $this->hydrator->hydrate(
            (object)['id' => 2],
            TestSchemaFactory::createPetSchema()
        );

        $this->assertInstanceOf(Pet::class, $actual);
        $this->assertSame(2, $actual->getId());
    }

    /**
     * @test
     */
    public function canHydrateTypedObjectRecursively()
    {
        /** @var Hydrator $parent */
        $parent = $stubHydrator = $this->getMockBuilder(Hydrator::class)->getMockForAbstractClass();
        $stubHydrator
            ->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $stubHydrator
            ->expects($this->any())
            ->method('hydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $this->hydrator->setParent($parent);

        /** @var Pet $actual */
        $actual = $this->hydrator->hydrate(
            (object)['id' => 2, 'category' => (object)[]],
            TestSchemaFactory::createPetSchema()
        );

        $this->assertInstanceOf(Category::class, $actual->getCategory());
    }
}
