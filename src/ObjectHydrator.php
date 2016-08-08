<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

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
            /** @var ScalarSchema $schema */
            return $this->dateTimeSerializer->serialize($node, $schema);
        }
        if ($schema instanceof ArraySchema) {
            return array_map(function ($value) use ($schema) {
                return $this->dehydrateNode($value, $schema->getItemsSchema());
            }, $node);
        }
        if ($schema instanceof ObjectSchema) {
            if (!$node instanceof \stdClass) {
                $class  = get_class($node);
                $offset = strlen($class) + 2;
                $data   = (array)$node;
                $array  = array_filter(array_combine(array_map(function ($k) use ($offset) {
                    return substr($k, $offset);
                }, array_keys($data)), array_values($data)));
                $node   = (object)$array;
            } else {
                $node = clone $node;
            }

            foreach ($node as $name => $value) {
                $node->$name = $schema->hasPropertySchema($name)
                    ? $this->dehydrateNode($value, $schema->getPropertySchema($name))
                    : $value;
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
}
