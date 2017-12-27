<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;

class ArrayHydrator extends Hydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * ArrayHydrator constructor.
     */
    public function __construct()
    {
        $this->anySchema = new AnySchema();
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     * @return array
     */
    public function hydrate($node, Schema $schema)
    {
        return $this->hydrateArray($node, $schema);
    }

    /**sz
     * @param mixed  $node
     * @param Schema $schema
     * @return array
     */
    private function hydrateArray(array $node, Schema $schema): array
    {
        $itemsSchema = $schema instanceof ArraySchema ? $schema->getItemsSchema() : $this->anySchema;

        return array_map(function ($value) use ($itemsSchema) {
            return $this->bubble($value, $itemsSchema);
        }, $node);
    }

    /**
     * @param        $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return is_array($data);
    }
}
