<?php declare(strict_types = 1);
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

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ObjectHydrator implements Hydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * @var bool
     */
    private $is32Bit;

    /**
     * @var DateTimeSerializer
     */
    private $dateTimeSerializer;

    /**
     * @var ClassNameResolver
     */
    private $classNameResolver;

    /**
     * ObjectHydrator constructor.
     *
     * @param ClassNameResolver  $classNameResolver
     * @param DateTimeSerializer $dateTimeSerializer
     * @param bool               $is32Bit
     */
    public function __construct(
        ClassNameResolver $classNameResolver,
        DateTimeSerializer $dateTimeSerializer = null,
        $is32Bit = null
    ) {

        $this->anySchema          = new AnySchema();
        $this->is32Bit            = $is32Bit !== null ? $is32Bit : PHP_INT_SIZE === 4;
        $this->dateTimeSerializer = $dateTimeSerializer ?: new DateTimeSerializer();
        $this->classNameResolver  = $classNameResolver;
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function hydrate($data, Schema $schema)
    {
        return $this->hydrateNode($data, $schema);
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($data, Schema $schema)
    {
        return $this->dehydrateNode($data, $schema);
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    private function hydrateNode($node, Schema $schema)
    {
        if ($schema instanceof ArraySchema) {
            return array_map(function ($value) use ($schema) {
                return $this->hydrateNode($value, $schema->getItemsSchema());
            }, $node);
        }
        if ($schema instanceof ObjectSchema) {
            if (!$schema->hasComplexType()) {
                $object = clone $node;
                foreach ($node as $name => $value) {
                    if ($schema->hasPropertySchema($name)) {
                        $object->$name = $this->hydrateNode($value, $schema->getPropertySchema($name));
                    }
                }

                return $object;
            }
            $fqcn = $this->classNameResolver->resolve($schema->getComplexType()->getName());;
            $object    = unserialize(sprintf('O:%d:"%s":0:{}', strlen($fqcn), $fqcn));
            $reflector = new \ReflectionObject($object);

            foreach ($node as $name => $value) {
                if (!$reflector->hasProperty($name)) {
                    continue;
                }

                if ($schema->hasPropertySchema($name)) {
                    $value = $this->hydrateNode($value, $schema->getPropertySchema($name));
                }

                $attribute = $reflector->getProperty($name);
                $attribute->setAccessible(true);
                $attribute->setValue($object, $value);
            }

            return $object;
        }

        /** @var ScalarSchema $schema */
        return $this->castScalarValue($node, $schema);
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    private function dehydrateNode($node, Schema $schema)
    {
        if ($node instanceof \DateTimeInterface) {
            return $this->dateTimeSerializer->serialize($node, $schema);
        }
        if ($this->shouldTreatAsArray($node, $schema)) {
            if ($schema instanceof ArraySchema) {
                return array_map(function ($value) use ($schema) {
                    return $this->dehydrateNode($value, $schema->getItemsSchema());
                }, $node);
            }

            return array_map(function ($value) {
                return $this->dehydrateNode($value, $this->anySchema);
            }, $node);
        }
        if ($this->shouldTreatAsObject($node, $schema)) {
            if (!$node instanceof \stdClass) {
                $data      = (object)[];
                $reflector = new \ReflectionObject($node);

                foreach ($reflector->getProperties() as $attribute) {
                    $attribute->setAccessible(true);
                    $data->{$attribute->getName()} = $attribute->getValue($node);
                }
                $node = $data;
            } else {
                $node = clone $node;
            }
            foreach ($node as $name => $value) {
                if ($schema instanceof ObjectSchema) {
                    $valueSchema = $schema->hasPropertySchema($name)
                        ? $schema->getPropertySchema($name)
                        : $this->anySchema;
                }
                $node->$name = $this->dehydrateNode($value, isset($valueSchema) ? $valueSchema : $this->anySchema);
            }

            return $node;
        }

        return $node;
    }

    /**
     * Cast a scalar value using the schema.
     *
     * @param mixed        $value
     * @param ScalarSchema $schema
     *
     * @return float|int|string|\DateTimeInterface
     * @throws UnsupportedException
     */
    private function castScalarValue($value, ScalarSchema $schema)
    {
        if ($schema->isType(Schema::TYPE_NUMBER)) {
            return ctype_digit($value) ? (int)$value : (float)$value;
        }
        if ($schema->isType(Schema::TYPE_INT)) {
            if ($this->is32Bit && $schema->hasFormat(Schema::FORMAT_INT64)) {
                throw new UnsupportedException("Operating system does not support 64 bit integers");
            }

            return (int)$value;
        }
        if ($schema->isDateTime()) {
            return $this->dateTimeSerializer->deserialize($value, $schema);
        }

        settype($value, $schema->getType());

        return $value;
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return bool
     */
    private function shouldTreatAsObject($node, Schema $schema): bool
    {
        return $schema instanceof ObjectSchema || $schema instanceof AnySchema && is_object($node);
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return bool
     */
    private function shouldTreatAsArray($node, Schema $schema): bool
    {
        return $schema instanceof ArraySchema || $schema instanceof AnySchema && is_array($node);
    }
}
