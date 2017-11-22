<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Hydrator\Exception\ClassNotFoundException;

class ClassNameResolver
{
    /**
     * @var array
     */
    private $resourceNamespaces = [];

    /**
     * ClassNameResolver constructor.
     *
     * @param array $resourceNamespaces
     */
    public function __construct(array $resourceNamespaces)
    {
        $this->resourceNamespaces = $resourceNamespaces;
    }

    /**
     * @param string $typeName
     *
     * @return string
     */
    public function resolve(string $typeName): string
    {
        foreach ($this->resourceNamespaces as $resourceNamespace) {
            if (class_exists($fqcn = $this->qualify($resourceNamespace, $typeName))) {
                return $fqcn;
            }
        }

        throw new ClassNotFoundException(
            sprintf("Did not find type '%s' in namespace(s) '%s'.", $typeName, implode(', ', $this->resourceNamespaces))
        );
    }

    /**
     * @param string $resourceNamespace
     * @param string $typeName
     *
     * @return string
     */
    protected function qualify(string $resourceNamespace, string $typeName): string
    {
        return ltrim("$resourceNamespace\\$typeName");
    }
}
