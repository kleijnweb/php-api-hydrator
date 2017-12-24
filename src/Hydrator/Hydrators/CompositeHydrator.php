<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;

class CompositeHydrator extends Hydrator
{
    /**
     * @var Hydrator[]
     */
    protected $children = [];

    /**
     * CompositeHydrator constructor.
     * @param Hydrator[] $children
     */
    public function __construct(array $children = [])
    {
        foreach ($children as $child) {
            $this->addChildStrategy($child);
        }
    }

    /**
     * @param Hydrator $child
     * @return CompositeHydrator
     */
    public function addChildStrategy(Hydrator $child): CompositeHydrator
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     * @return mixed
     */
    public function hydrate($node, Schema $schema)
    {
        foreach ($this->children as $child) {
            if ($child->supports($node, $schema)) {
                return $child->hydrate($child->applyDefaults($node, $schema), $schema);
            }
        }
        throw new UnsupportedException("No child strategy");
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        foreach ($this->children as $child) {
            if ($child->supports($data, $schema)) {
                return true;
            }
        }

        return false;
    }
}
