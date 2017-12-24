<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\SchemaDehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\SchemaNodeDehydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\SchemaHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\SchemaNodeHydrator;


/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ObjectHydrator implements Hydrator
{
    /**
     * @var SchemaHydrator
     */
    private $nodeHydrator;

    /**
     * @var SchemaDehydrator
     */
    private $nodeDehydrator;

    /**
     * ObjectHydrator constructor.
     *
     * @param ClassNameResolver  $classNameResolver
     * @param DateTimeSerializer $dateTimeSerializer
     * @param bool               $is32Bit
     */
    public function __construct(
        ClassNameResolver $classNameResolver,
        DateTimeSerializer $dateTimeSerializer = null,
        $is32Bit = null
    ) {
        $is32Bit              = $is32Bit !== null ? $is32Bit : PHP_INT_SIZE === 4;
        $dateTimeSerializer   = $dateTimeSerializer ?: new DateTimeSerializer();
        $this->nodeDehydrator = new SchemaNodeDehydrator($dateTimeSerializer);
        $this->nodeHydrator   = new SchemaNodeHydrator($classNameResolver, $dateTimeSerializer, $is32Bit);
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function hydrate($data, Schema $schema)
    {
        return $this->nodeHydrator->hydrate($data, $schema);
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($data, Schema $schema)
    {
        return $this->nodeDehydrator->dehydrate($data, $schema);
    }
}
