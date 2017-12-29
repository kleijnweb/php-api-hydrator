<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Processors\Object;

class ComplexTypePropertyProcessor extends ComplexTypeProcessor
{
    /**
     * @param \stdClass $input
     * @return object
     */
    protected function hydrateObject(\stdClass $input)
    {
        $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->className), $this->className));

        foreach ($this->reflectionProperties as $name => $reflectionProperty) {

            if (isset($this->reflectionProperties[$name])) {
                if (!property_exists($input, $name)) {
                    if (!isset($this->defaults[$name])) {
                        continue;
                    }
                    $value = $this->defaults[$name];
                } else {
                    $value = $input->$name;
                }

                if (isset($this->propertyProcessors[$name])) {
                    $this->reflectionProperties[$name]->setValue(
                        $object,
                        $this->hydrateProperty($name, $value)
                    );
                }
            }
        }

        return $object;
    }
}
