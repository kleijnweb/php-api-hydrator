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
use KleijnWeb\PhpApi\Hydrator\Hydrator\DefaultCompositeHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\SchemaHydrator;


/**
 * @author John Kleijn <john@kleijnweb.nl>
 * @deprecated
 */
class ObjectHydrator implements \KleijnWeb\PhpApi\Hydrator\Hydrator, SchemaDehydrator, SchemaHydrator
{
    /**
     * @var Hydrator
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
     * @param bool               $force32Bit
     */
    public function __construct(
        ClassNameResolver $classNameResolver,
        DateTimeSerializer $dateTimeSerializer = null,
        $force32Bit = false
    ) {
        $this->nodeDehydrator = new SchemaNodeDehydrator($dateTimeSerializer);
        $this->nodeHydrator   = new DefaultCompositeHydrator($classNameResolver, $dateTimeSerializer);
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
