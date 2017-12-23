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
        $node = $this->applyDefaults($node, $schema);

        if ($schema instanceof AnySchema) {
            if (is_array($node)) {
                return array_map(function ($value) use ($schema) {
                    return $this->hydrateNode($value, $this->anySchema);
                }, $node);
            } elseif (is_object($node)) {
                $object = (object)[];
                foreach ($node as $property => $value) {
                    $object->$property = $this->hydrateNode($value, $this->anySchema);
                }

                return $object;
            }
            if (is_numeric($node)) {
                return ctype_digit($node) ? (int)$node : (float)$node;
            }
            try {
                $node = $this->dateTimeSerializer->deserialize($node, $schema);
            } catch (\Throwable $e) {
                return $node;
            }

        } elseif ($schema instanceof ArraySchema) {
            return array_map(function ($value) use ($schema) {
                return $this->hydrateNode($value, $schema->getItemsSchema());
            }, $node);
        } elseif ($schema instanceof ObjectSchema) {
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
        } elseif ($schema instanceof ScalarSchema) {
            return $this->castScalarValue($node, $schema);
        }

        return $node;
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
            $node = $this->dateTimeSerializer->serialize($node, $schema);
        } elseif ($this->shouldTreatAsArray($node, $schema)) {
            $node = array_map(function ($value) use ($schema) {
                $schema = $schema instanceof ArraySchema ? $schema->getItemsSchema() : $this->anySchema;

                return $this->dehydrateNode($value, $schema);
            }, $node);
        } elseif ($this->shouldTreatAsObject($node, $schema)) {
            $input = $node;
            $node  = (object)[];

            if (!$input instanceof \stdClass) {
                $input = $this->extractValuesFromTypedObject($input);
            }

            foreach ($input as $name => $value) {

                $valueSchema = $schema instanceof ObjectSchema && $schema->hasPropertySchema($name)
                    ? $schema->getPropertySchema($name)
                    : $this->anySchema;

                if ($this->isAllowedNull($value, $valueSchema)) {
                    $node->$name = null;
                    continue;
                }

                if (null !== $value) {
                    $node->$name = $this->dehydrateNode(
                        $value,
                        $valueSchema
                    );
                }
            }
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

    /**
     * @param mixed  $value
     * @param Schema $schema
     * @return bool
     */
    private function isAllowedNull($value, Schema $schema): bool
    {
        return $value === null && $schema instanceof ScalarSchema && $schema->isType(Schema::TYPE_NULL);
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    private function applyDefaults($node, Schema $schema)
    {
        if ($node instanceof \stdClass && $schema instanceof ObjectSchema) {
            /** @var Schema $propertySchema */
            foreach ($schema->getPropertySchemas() as $name => $propertySchema) {
                if (!isset($node->$name) && null !== $default = $propertySchema->getDefault()) {
                    $node->$name = $default;
                }
            }
        }

        return $node === null ? $schema->getDefault() : $node;
    }

    /**
     * @param $node
     * @return \stdClass
     */
    private function extractValuesFromTypedObject($node): array
    {
        $reflector  = new \ReflectionObject($node);
        $properties = $reflector->getProperties();
        $data       = [];
        foreach ($properties as $attribute) {
            $attribute->setAccessible(true);
            $data[$attribute->getName()] = $attribute->getValue($node);
        }

        return $data;
    }
}
