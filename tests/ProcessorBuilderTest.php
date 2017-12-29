<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\ComplexType;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Processors\AnyProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\ArrayProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Object\ComplexTypePropertyProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Object\LooseSimpleObjectProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Object\StrictSimpleObjectProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\BoolProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\DateTimeProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\IntegerProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NullProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NumberProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\StringProcessor;
use KleijnWeb\PhpApi\Hydrator\Tests\TestHelperFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ProcessorBuilderTest extends TestCase
{
    /**
     * @param Schema $schema
     * @param string $expectedType
     * @test
     * @dataProvider dataProvider
     */
    public function willCreateCorrectProcessorForSchemas(Schema $schema, string $expectedType)
    {
        $builder = new ProcessorBuilder(
            TestHelperFactory::createClassNameResolver(),
            new DateTimeSerializer()
        );
        $this->assertInstanceOf(
            $expectedType,
            $builder->build($schema)
        );
    }

    /**
     * @test
     */
    public function willFailOnUnknownSchema()
    {
        $builder = new ProcessorBuilder(
            TestHelperFactory::createClassNameResolver(),
            new DateTimeSerializer()
        );
        $this->expectException(UnsupportedException::class);

        /** @var Schema $schema */
        $schema = $this->getMockBuilder(Schema::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $builder->build($schema);
    }

    public static function dataProvider(): array
    {
        return [
            [new ScalarSchema((object)['type' => Schema::TYPE_BOOL]), BoolProcessor::class],
            [new ScalarSchema((object)['type' => Schema::TYPE_INT]), IntegerProcessor::class],
            [new ScalarSchema((object)['type' => Schema::TYPE_NUMBER]), NumberProcessor::class],
            [new ScalarSchema((object)['type' => Schema::TYPE_NULL]), NullProcessor::class],
            [new ScalarSchema((object)['type' => Schema::TYPE_STRING]), StringProcessor::class],
            [
                new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE]),
                DateTimeProcessor::class,
            ],
            [
                new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE_TIME]),
                DateTimeProcessor::class,
            ],
            [new AnySchema(), AnyProcessor::class],
            [new ArraySchema((object)[], new AnySchema()), ArrayProcessor::class],
            [new ObjectSchema((object)[], (object)['id' => new AnySchema(),]), LooseSimpleObjectProcessor::class],
            [
                new ObjectSchema((object)['additionalProperties' => false], (object)['id' => new AnySchema(),]),
                StrictSimpleObjectProcessor::class,
            ],
            [self::createComplexSchema(), ComplexTypePropertyProcessor::class,],
        ];
    }


    public static function createComplexSchema(): ObjectSchema
    {
        $schema = new ObjectSchema((object)[], (object)['id' => new AnySchema()]);
        $schema->setComplexType(new ComplexType('Pet', $schema));

        return $schema;
    }
}
