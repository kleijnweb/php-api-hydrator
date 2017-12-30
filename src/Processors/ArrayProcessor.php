<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Processors;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ArrayProcessor extends Processor
{
    /**
     * @var Processor
     */
    private $itemsProcessor;

    /**
     * ArrayHydrator constructor.
     * @param ArraySchema $schema
     */
    public function __construct(ArraySchema $schema)
    {
        parent::__construct($schema);
    }

    /**
     * @param Processor $itemsHydrator
     */
    public function setItemsProcessor(Processor $itemsHydrator)
    {
        $this->itemsProcessor = $itemsHydrator;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function hydrate($value)
    {
        return array_map(function ($value) {
            return $this->itemsProcessor->hydrate($value);
        }, $value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function dehydrate($value)
    {
        return array_map(function ($value) {
            return $this->itemsProcessor->dehydrate($value);
        }, $value);
    }
}
