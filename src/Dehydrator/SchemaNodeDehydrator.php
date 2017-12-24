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
     * SchemaNodeDehydrator constructor.
     *
     * @param DateTimeSerializer  $dateTimeSerializer
     */
    public function __construct(DateTimeSerializer $dateTimeSerializer)
    {
        $this->anySchema          = new AnySchema();
        $this->dateTimeSerializer = $dateTimeSerializer;
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($node, Schema $schema)
    {
        if ($node instanceof \DateTimeInterface) {
            return $this->dehydrateDateTime($node, $schema);
        } elseif (is_object($node)) {
            return $this->dehydrateObject($node, $schema);
        } elseif (is_array($node)) {
            return $this->dehydrateArray($node, $schema);
        }

        return $node;
    }

    /**
     * @param \DateTimeInterface $value
     * @param Schema             $schema
     * @return string
     */
    private function dehydrateDateTime(\DateTimeInterface $value, Schema $schema): string
    {
        return $this->dateTimeSerializer->serialize($value, $schema);
    }

    /**
     * @param array  $array
     * @param Schema $schema
     * @return array
     */
    private function dehydrateArray(array $array, Schema $schema): array
    {
        return array_map(function ($value) use ($schema) {
            $schema = $schema instanceof ArraySchema ? $schema->getItemsSchema() : $this->anySchema;

            return $this->dehydrate($value, $schema);
        }, $array);
    }

    /**
     * @param object $input
     * @param Schema $schema
     * @return \stdClass
     */
    private function dehydrateObject($input, Schema $schema): \stdClass
    {
        $object = $input instanceof \stdClass ? $input : new ReflectingObjectIterator($input);
        $node   = (object)[];

        foreach ($object as $name => $value) {

            $valueSchema = $schema instanceof ObjectSchema && $schema->hasPropertySchema($name)
                ? $schema->getPropertySchema($name)
                : $this->anySchema;

            if ($value === null) {
                $isScalarNull = ($valueSchema instanceof ScalarSchema && $valueSchema->isType(Schema::TYPE_NULL));
                if ($isScalarNull || $input instanceof \stdClass) {
                    $node->$name = null;
                    continue;
                }
            } else {
                $node->$name = $this->dehydrate(
                    $value,
                    $valueSchema
                );
            }
        }

        return $node;
    }
}
