<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;

class ArrayDehydrator extends Dehydrator
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
    public function dehydrate($node, Schema $schema)
    {
        return $this->dehydrateArray($node, $schema);
    }

    /**
     * @param array  $array
     * @param Schema $schema
     * @return array
     */
    private function dehydrateArray(array $array, Schema $schema): array
    {
        return array_map(function ($value) use ($schema) {
            $schema = $schema instanceof ArraySchema ? $schema->getItemsSchema() : $this->anySchema;

            return $this->bubble($value, $schema);
        }, $array);
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
