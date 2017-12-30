<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

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
use KleijnWeb\PhpApi\Hydrator\Processors\Processor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\BoolProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\DateTimeProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\IntegerProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NullProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NumberProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\StringProcessor;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ProcessorBuilder
{
    /**
     * @var Processor[]
     */
    private $hydrators = [];

    /**
     * @var ClassNameResolver
     */
    private $classNameResolver;

    /**
     * @var DateTimeSerializer
     */
    private $dateTimeSerializer;

    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * @var array
     */
    private static $primitiveMap = [
        Schema::TYPE_STRING => StringProcessor::class,
        Schema::TYPE_INT    => IntegerProcessor::class,
        Schema::TYPE_BOOL   => BoolProcessor::class,
        Schema::TYPE_NUMBER => NumberProcessor::class,
        Schema::TYPE_NULL   => NullProcessor::class,
    ];

    /**
     * HydratorBuilder constructor.
     * @param ClassNameResolver  $classNameResolver
     * @param DateTimeSerializer $dateTimeSerializer
     */
    public function __construct(ClassNameResolver $classNameResolver, DateTimeSerializer $dateTimeSerializer = null)
    {
        $this->classNameResolver  = $classNameResolver;
        $this->dateTimeSerializer = $dateTimeSerializer ?: new DateTimeSerializer();
        $this->anySchema          = new AnySchema();
    }

    public function build(Schema $schema): Processor
    {
        if ($schema instanceof ObjectSchema) {
            if ($schema->hasComplexType()) {
                /** @var \KleijnWeb\PhpApi\Hydrator\Processors\Object\ComplexTypePropertyProcessor $hydrator */
                $hydrator = $this->getHydrator($schema, ComplexTypePropertyProcessor::class);
            } else {
                // TODO Add support in Schema
                if (!isset($schema->getDefinition()->additionalProperties)
                    || $schema->getDefinition()->additionalProperties) {
                    /** @var LooseSimpleObjectProcessor $hydrator */
                    $hydrator = $this->getHydrator($schema, LooseSimpleObjectProcessor::class);
                } else {
                    /** @var StrictSimpleObjectProcessor $hydrator */
                    $hydrator = $this->getHydrator($schema, StrictSimpleObjectProcessor::class);
                }
            }
            foreach ($schema->getPropertySchemas() as $propertyName => $propertySchema) {
                $hydrator->setPropertyProcessor($propertyName, $this->build($propertySchema));
            }

            return $hydrator;
        }
        if ($schema instanceof ArraySchema) {
            /** @var ArrayProcessor $hydrator */
            $hydrator = $this->getHydrator($schema, ArrayProcessor::class);
            $hydrator->setItemsProcessor($this->build($schema->getItemsSchema()));

            return $hydrator;
        }
        if ($schema instanceof ScalarSchema) {
            if ($schema->isDateTime()) {
                return $this->getHydrator($schema, DateTimeProcessor::class);
            }
            if (isset(self::$primitiveMap[$schema->getType()])) {
                return $this->getHydrator($schema, self::$primitiveMap[$schema->getType()]);
            }
        }
        if ($schema instanceof AnySchema) {
            return $this->getHydrator($schema, AnyProcessor::class);
        }
        throw new UnsupportedException("Unsupported schema type " . get_class($schema));
    }

    private function getHydrator(Schema $schema, string $className): Processor
    {
        $schemaHash = spl_object_hash($schema);

        if (!isset($this->hydrators[$className][$schemaHash])) {
            switch ($className) {
                case LooseSimpleObjectProcessor::class:
                    /** @var ObjectSchema $schema */
                    $objectSchema = $schema;
                    /** @var AnyProcessor $anyHydrator */
                    $anyHydrator = $this->getHydrator($this->anySchema, AnyProcessor::class);
                    $hydrator    = new LooseSimpleObjectProcessor($objectSchema, $anyHydrator);
                    break;
                case ComplexTypePropertyProcessor::class:
                    /** @var ObjectSchema $schema */
                    $objectSchema = $schema;
                    $className    = $this->classNameResolver->resolve($schema->getComplexType()->getName());
                    $hydrator     = new ComplexTypePropertyProcessor($objectSchema, $className);
                    break;
                case StrictSimpleObjectProcessor::class:
                    /** @var ObjectSchema $schema */
                    $objectSchema = $schema;
                    $hydrator     = new StrictSimpleObjectProcessor($objectSchema);
                    break;
                case DateTimeProcessor::class:
                case AnyProcessor::class:
                    $hydrator = new $className($schema, $this->dateTimeSerializer);
                    break;
                default:
                    $hydrator = new $className($schema);
            }
            $this->hydrators[$className][$schemaHash] = $hydrator;
        }

        return $this->hydrators[$className][$schemaHash];
    }
}
