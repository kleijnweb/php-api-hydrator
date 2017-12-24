<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\ObjectFactory;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\SchemaHydrator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
abstract class ObjectFactory
{
    /**
     * @var SchemaHydrator
     */
    protected $hydrator;

    /**
     * PropertyObjectFactory constructor.
     * @param SchemaHydrator $hydrator
     */
    public function __construct(SchemaHydrator $hydrator = null)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @param SchemaHydrator $hydrator
     */
    public function setHydrator(SchemaHydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @param \stdClass $dict
     * @param string    $fqcn
     * @param Schema    $schema
     * @return object
     */
    abstract public function create(\stdClass $dict, string $fqcn, Schema $schema);
}
