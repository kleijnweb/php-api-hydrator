<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

class ReflectingObjectIterator implements \Iterator
{
    /**
     * @var \ReflectionProperty[]
     */
    private $properties;

    /**
     * @var
     */
    private $object;

    /**
     * @param $object
     */
    public function __construct($object)
    {
        $reflector        = new \ReflectionObject($object);
        $this->properties = $reflector->getProperties();
        foreach ($this->properties as $attribute) {
            $attribute->setAccessible(true);
        }
        $this->object = $object;
    }

    /**
     * Return the current element
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        if (false === $property = current($this->properties)) {
            throw new \OutOfRangeException();
        }

        return $property->getValue($this->object);
    }

    /**
     * Move forward to next element
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->properties);
    }

    /**
     * Return the key of the current element
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        if (false === $property = current($this->properties)) {
            throw new \OutOfRangeException();
        }

        return $property->getName();
    }

    /**
     * Checks if current position is valid
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return false !== current($this->properties);
    }

    /**
     * Rewind the Iterator to the first element
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->properties);
    }
}
