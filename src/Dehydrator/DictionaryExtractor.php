<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DictionaryExtractor
{
    /**
     * @param $node
     * @return \stdClass
     */
    public function extractValuesFromTypedObject($node): array
    {
        $reflector  = new \ReflectionObject($node);
        $properties = $reflector->getProperties();
        $data       = [];
        foreach ($properties as $attribute) {
            $attribute->setAccessible(true);
            $value = $attribute->getValue($node);
            $data[$attribute->getName()] = $value;
        }

        return $data;
    }
}
