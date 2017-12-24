<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\ObjectFactory;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;

/**
 * Creates objects by bypassing the constructor and setting properties directly
 *
 * @author John Kleijn <john@kleijnweb.nl>
 */
class PropertyObjectFactory extends ObjectFactory
{
    /**
     * @param \stdClass $dict
     * @param string    $fqcn
     * @param Schema    $schema
     * @return object
     */
    public function create(\stdClass $dict, string $fqcn, Schema $schema)
    {
        $object    = unserialize(sprintf('O:%d:"%s":0:{}', strlen($fqcn), $fqcn));
        $reflector = new \ReflectionObject($object);

        foreach ($dict as $name => $value) {
            if (!$reflector->hasProperty($name)) {
                continue;
            }

            if ($schema instanceof ObjectSchema) {
                if ($schema->hasPropertySchema($name)) {
                    $value = $this->hydrator->hydrate($value, $schema->getPropertySchema($name));
                }
            }

            $attribute = $reflector->getProperty($name);
            $attribute->setAccessible(true);
            $attribute->setValue($object, $value);
        }

        return $object;
    }
}
