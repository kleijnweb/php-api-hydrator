<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;

class SimpleObjectHydrator extends Hydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * SimpleObjectHydrator constructor.
     */
    public function __construct()
    {
        $this->anySchema = new AnySchema();
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    public function hydrate($node, Schema $schema)
    {
        return $this->hydrateSimpleObject($node, $schema);
    }

    /**
     * @param \stdClass $node
     * @param Schema    $schema
     * @return mixed
     */
    private function hydrateSimpleObject(\stdClass $node, Schema $schema)
    {
        $object = clone $node;
        foreach ($node as $name => $value) {
            $valueSchema = $schema instanceof ObjectSchema && $schema->hasPropertySchema($name)
                ? $schema->getPropertySchema($name)
                : $this->anySchema;

            $object->$name = $this->bubble($value, $valueSchema);
        }

        return $object;
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return $data instanceof \stdClass;
    }
}
