<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\AnyFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\ArrayFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\ComplexTypeFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\DateTimeFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\Factory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\FactoryQueue;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\LooseSimpleObjectFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\ScalarFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Factory\StrictSimpleObjectFactory;
use KleijnWeb\PhpApi\Hydrator\Processors\Processor;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ProcessorBuilder
{
    /**
     * @var FactoryQueue
     */
    protected $factoryQueue;

    /**
     * ProcessorBuilder constructor.
     * @param ClassNameResolver       $classNameResolver
     * @param DateTimeSerializer|null $dateTimeSerializer
     */
    public function __construct(ClassNameResolver $classNameResolver, DateTimeSerializer $dateTimeSerializer = null)
    {
        $dateTimeSerializer = $dateTimeSerializer ?: new DateTimeSerializer();

        $this->factoryQueue = new FactoryQueue(
            new ComplexTypeFactory($classNameResolver),
            new LooseSimpleObjectFactory(),
            new StrictSimpleObjectFactory(),
            new ArrayFactory(),
            new DateTimeFactory($dateTimeSerializer),
            new ScalarFactory(),
            new AnyFactory($dateTimeSerializer)
        );
    }

    /**
     * @param Factory $factory
     * @return ProcessorBuilder
     */
    public function add(Factory $factory): ProcessorBuilder
    {
        $this->factoryQueue->add($factory);

        return $this;
    }

    /**
     * @param Schema $schema
     * @return Processor
     */
    public function build(Schema $schema): Processor
    {
        /** @var Factory $factory */
        foreach (clone $this->factoryQueue as $factory) {
            if ($processor = $factory->create($schema, $this)) {
                return $processor;
            }
        }
        throw new UnsupportedException("Unsupported schema type " . get_class($schema));
    }
}
