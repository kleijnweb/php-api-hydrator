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
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;

class ComplexTypeObjectHydrator extends Hydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * @var ClassNameResolver
     */
    private $classNameResolver;

    /**
     * ComplexTypeObjectHydrator constructor.
     * @param ClassNameResolver $classNameResolver
     */
    public function __construct(ClassNameResolver $classNameResolver)
    {
        $this->anySchema         = new AnySchema();
        $this->classNameResolver = $classNameResolver;
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    public function hydrate($node, Schema $schema)
    {
        /** @var ObjectSchema $schema */
        return $this->hydrateComplexTypeObject($node, $schema);
    }

    /**
     * @param \stdClass    $node
     * @param ObjectSchema $schema
     * @return object
     */
    private function hydrateComplexTypeObject(\stdClass $node, ObjectSchema $schema)
    {
        $fqcn   = $this->classNameResolver->resolve($schema->getComplexType()->getName());
        $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($fqcn), $fqcn));

        $reflector = new \ReflectionObject($object);

        foreach ($node as $name => $value) {
            if (!$reflector->hasProperty($name)) {
                continue;
            }

            if ($schema->hasPropertySchema($name)) {
                $value = $this->bubble($value, $schema->getPropertySchema($name));
            }

            $attribute = $reflector->getProperty($name);
            $attribute->setAccessible(true);
            $attribute->setValue($object, $value);
        }

        return $object;
    }

    /**
     * @param        $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return $schema instanceof ObjectSchema && $schema->hasComplexType();
    }
}
