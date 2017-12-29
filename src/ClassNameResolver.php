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
     * @var array
     */
    private $cache = [];

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
        if (!isset($this->cache[$typeName])) {
            foreach ($this->resourceNamespaces as $resourceNamespace) {
                if (class_exists($this->cache[$typeName] = $this->qualify($resourceNamespace, $typeName))) {
                    return $this->cache[$typeName];
                }
            }

            throw new ClassNotFoundException(
                sprintf(
                    "Did not find type '%s' in namespace(s) '%s'.", $typeName,
                    implode(', ', $this->resourceNamespaces))
            );
        }

        return $this->cache[$typeName];
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
