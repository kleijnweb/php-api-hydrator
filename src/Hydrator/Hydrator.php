<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
abstract class Hydrator implements SchemaHydrator
{
    /**
     * @var Hydrator|null
     */
    protected $parent;

    /**
     * @param Hydrator $parent
     * @return Hydrator
     */
    public function setParent(Hydrator $parent): Hydrator
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return mixed
     */
    public function bubble($data, Schema $schema)
    {
        if (!$this->parent) {
            throw new UnsupportedException("Cannot bubble, no parent");
        }
        if (!$this->parent->supports($data, $schema)) {
            return $this->parent->bubble($data, $schema);
        }

        return $this->parent->hydrate($data, $schema);
    }

    /**
     * @param        $data
     * @param Schema $schema
     * @return bool
     */
    abstract public function supports($data, Schema $schema): bool;

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    abstract public function hydrate($data, Schema $schema);

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    protected function applyDefaults($node, Schema $schema)
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
}
