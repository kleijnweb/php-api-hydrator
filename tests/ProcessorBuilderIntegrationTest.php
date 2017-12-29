<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\ProcessorBuilder;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ProcessorBuilderIntegrationTest extends TestCase
{
    const BENCH_SIZE = 1000;

    /**
     * @var int
     */
    private static $baseLine;

    /**
     * @var ProcessorBuilder
     */
    private $processorBuilder;

    /**
     * @var ClassNameResolver
     */
    private $classNameResolver;

    public static function setUpBeforeClass()
    {
        self::$baseLine = self::bench(function () {
            $input = [];
            for ($i = 0; $i < self::BENCH_SIZE * 10; ++$i) {
                $input[] = self::createTestStructure();
            }
            $output = [];
            foreach ($input as $item) {
                $output = var_export($item, true);
            }

            return $output;
        });
    }

    protected function setUp()
    {
        $this->classNameResolver = new ClassNameResolver(['KleijnWeb\PhpApi\Hydrator\Tests\Types']);
        $this->processorBuilder  = new ProcessorBuilder($this->classNameResolver);
    }

    /**
     * @test
     */
    public function canHydratePetUsingFullSchema()
    {
        $petSchema = TestHelperFactory::createFullPetSchema();

        $hydrator = $this->processorBuilder->build($petSchema);

        $input = self::createTestStructure();

        /** @var Pet $pet */
        $pet = $hydrator->hydrate($input);

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
        $this->assertInstanceOf(\DateTime::class, $pet->getRating()->created);
    }

    /**
     * @test
     * @group perf
     */
    public function canHydrateLargeArrayUsingPartialSchemaQuickly()
    {
        $input = [];

        for ($i = 0; $i < self::BENCH_SIZE; ++$i) {
            $input[] = self::createTestStructure();
        }

        $processor = $this->processorBuilder->build(
            $schema = new ArraySchema((object)[], TestHelperFactory::createPartialPetSchema())
        );

        $this->assertAcceptablePerformance(function () use ($processor, $input) {
            $processor->hydrate($input);
        });
    }

    /**
     * @test
     * @group perf
     */
    public function canHydrateLargeArrayUsingFullSchemaSchemaQuickly()
    {
        $input = [];

        for ($i = 0; $i < self::BENCH_SIZE; ++$i) {
            $input[] = self::createTestStructure();
        }

        $processor = $this->processorBuilder->build(
            $schema = new ArraySchema((object)[], TestHelperFactory::createFullPetSchema())
        );

        $this->assertAcceptablePerformance(function () use ($processor, $input) {
            $processor->hydrate($input);
        });
    }

    /**
     * @test
     */
    public function canRoundTrip()
    {
        $schema = TestHelperFactory::createFullPetSchema();

        $input = self::createTestStructure();

        $processor = $this->processorBuilder->build($schema);

        /** @var Pet $pet */
        $pet = $processor->hydrate($input);

        // Making sure the input is unaffected
        $this->assertObjectNotHasAttribute('id', $input->category);

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
        $this->assertInstanceOf(\DateTime::class, $pet->getRating()->created);

        $output = $processor->dehydrate($pet);

        unset($input->x);
        $this->assertEquals($input, $output);
    }

    /**
     * @test
     * @group perf
     */
    public function canDehydrateQuickly()
    {
        $input = [];

        for ($i = 0; $i < self::BENCH_SIZE; ++$i) {
            $input[] = self::createTestStructure();
        }

        $processor = $this->processorBuilder->build(
            new ArraySchema((object)[], TestHelperFactory::createFullPetSchema())
        );

        $input = $processor->hydrate($input);

        $this->assertAcceptablePerformance(function () use ($processor, $input) {
            $processor->dehydrate($input);
        });
    }

    /**
     * @test
     * @group perf
     */
    public function canHydrateSimpleObjectsQuickly()
    {
        $input = [];

        for ($i = 0; $i < self::BENCH_SIZE; ++$i) {
            $input[] = self::createTestStructure();
        }

        $processorWithoutComplexTypes = $this->processorBuilder->build(
            new ArraySchema((object)[], TestHelperFactory::createFullPetSchema(false))
        );


        $this->assertAcceptablePerformance(function () use ($processorWithoutComplexTypes, $input) {
            $processorWithoutComplexTypes->hydrate($input);
        });
    }

    /**
     * @param callable $fn
     * @return int
     */
    private static function bench(callable $fn)
    {
        $start = microtime(true);
        $fn();
        $elapsedMilliseconds = (int)round((microtime(true) - $start) * 1000);

        return $elapsedMilliseconds;
    }

    /**
     * @return \stdClass
     */
    private static function createTestStructure(): \stdClass
    {
        return (object)[
            'id'        => rand(),
            'name'      => (string)rand(),
            'status'    => (string)rand(),
            'photoUrls' => [' / ' . (string)rand(), ' / ' . (string)rand()],
            'price'     => (float)rand() . '.25',
            'category'  => (object)[
                'name' => 'Shepherd',
            ],
            'tags'      => [
                (object)['name' => (string)rand()],
                (object)['name' => (string)rand()],
            ],
            'rating'    => (object)[
                'value'   => 10,
                'created' => '2016-01-01',
            ],
        ];
    }

    /**
     * @param callable $fn
     */
    private function assertAcceptablePerformance(callable $fn)
    {
        $this->assertLessThan(self::$baseLine, $this->bench($fn));
    }
}
