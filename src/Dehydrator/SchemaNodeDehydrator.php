<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class SchemaNodeDehydrator implements SchemaDehydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * @var DateTimeSerializer
     */
    private $dateTimeSerializer;

    /**
     * NodeDehydrator constructor.
     * @param DateTimeSerializer|null $dateTimeSerializer
     */
    public function __construct(DateTimeSerializer $dateTimeSerializer = null)
    {
        $this->anySchema          = new AnySchema();
        $this->dateTimeSerializer = $dateTimeSerializer ?: new DateTimeSerializer();
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
    private function dehydrateNode($node, Schema $schema)
    {
        if ($node instanceof \DateTimeInterface) {
            $node = $this->dateTimeSerializer->serialize($node, $schema);

        } elseif ($this->shouldTreatAsObject($node, $schema)) {
            $input = $this->isAssociativeArray($node) ? (object)$node : $node;
            $node  = (object)[];

            $wasTyped = false;
            if (!$input instanceof \stdClass) {
                $wasTyped = true;
                $input = $this->extractValuesFromTypedObject($input);
            }

            foreach ($input as $name => $value) {

                $valueSchema = $schema instanceof ObjectSchema && $schema->hasPropertySchema($name)
                    ? $schema->getPropertySchema($name)
                    : $this->anySchema;

                if ($value === null && !$wasTyped || $this->isAllowedNull($value, $valueSchema)) {
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

        } elseif ($this->shouldTreatAsArray($node, $schema)) {
            $node = array_map(function ($value) use ($schema) {
                $schema = $schema instanceof ArraySchema ? $schema->getItemsSchema() : $this->anySchema;

                return $this->dehydrateNode($value, $schema);
            }, $node);
        }

        return $node;
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return bool
     */
    private function shouldTreatAsObject($node, Schema $schema): bool
    {
        return $schema instanceof ObjectSchema
            ||
            $schema instanceof AnySchema && (
                is_object($node) || $this->isAssociativeArray($node)
            );
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return bool
     */
    private function shouldTreatAsArray($node, Schema $schema): bool
    {
        return $schema instanceof ArraySchema || $schema instanceof AnySchema && is_array($node) && !$this->isAssociativeArray($node);
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return bool
     */
    private function isAssociativeArray($node): bool
    {
        return is_array($node) && !isset($node[0]);
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
