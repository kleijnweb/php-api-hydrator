<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;

class CompositeDehydrator extends Dehydrator
{
    /**
     * @var Dehydrator[]
     */
    protected $children = [];

    /**
     * CompositeHydrator constructor.
     * @param Dehydrator[] $children
     */
    public function __construct(array $children = [])
    {
        foreach ($children as $child) {
            $this->addChildStrategy($child);
        }
    }

    /**
     * @param Dehydrator $child
     * @return CompositeDehydrator
     */
    public function addChildStrategy(Dehydrator $child): CompositeDehydrator
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
    public function dehydrate($node, Schema $schema)
    {
        foreach ($this->children as $child) {
            if ($child->supports($node, $schema)) {
                return $child->dehydrate($node, $schema);
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
