<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
abstract class Dehydrator implements SchemaDehydrator
{
    /**
     * @var Dehydrator|null
     */
    protected $parent;

    /**
     * @param Dehydrator $parent
     * @return Dehydrator
     */
    public function setParent(Dehydrator $parent): Dehydrator
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

        return $this->parent->dehydrate($data, $schema);
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return bool
     */
    abstract public function supports($data, Schema $schema): bool;
}
