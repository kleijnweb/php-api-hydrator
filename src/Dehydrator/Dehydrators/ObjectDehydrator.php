<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\ReflectingObjectIterator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;

class ObjectDehydrator extends Dehydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * SimpleObjectHydrator constructor.
     */
    public function __construct()
    {
        $this->anySchema = new AnySchema();
    }

    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($node, Schema $schema)
    {
        return $this->dehydrateObject($node, $schema);
    }


    /**
     * @param object $input
     * @param Schema $schema
     * @return \stdClass
     */
    private function dehydrateObject($input, Schema $schema): \stdClass
    {
        $object = $input instanceof \stdClass ? $input : new ReflectingObjectIterator($input);
        $node   = (object)[];

        foreach ($object as $name => $value) {

            $valueSchema = $schema instanceof ObjectSchema && $schema->hasPropertySchema($name)
                ? $schema->getPropertySchema($name)
                : $this->anySchema;

            if ($value === null) {
                $isScalarNull = ($valueSchema instanceof ScalarSchema && $valueSchema->isType(Schema::TYPE_NULL));
                if ($isScalarNull || $input instanceof \stdClass) {
                    $node->$name = null;
                    continue;
                }
            } else {
                $node->$name = $this->bubble(
                    $value,
                    $valueSchema
                );
            }
        }

        return $node;
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return is_object($data);
    }
}
