<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

class ComplexObjectIteratorFactory
{
    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct(string $type = ReflectingObjectIterator::class)
    {
        $this->type = $type;
    }

    /**
     * @param $object
     * @return \Iterator
     */
    public function create($object): \Iterator
    {
        return new $this->type($object);
    }
}
