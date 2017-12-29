<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Processors;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;

class AnyProcessor extends Processor
{
    /**
     * @var bool
     */
    private $is32Bit;

    /**
     * @var DateTimeSerializer
     */
    private $dateTimeSerializer;


    /**
     * ScalarVisitor constructor.
     * @param DateTimeSerializer|null $dateTimeSerializer
     * @param bool                    $force32Bit
     */
    public function __construct(AnySchema $schema, DateTimeSerializer $dateTimeSerializer, $force32Bit = false)
    {
        parent::__construct($schema);

        $this->is32Bit            = $force32Bit === true ? true : PHP_INT_SIZE === 4;
        $this->dateTimeSerializer = $dateTimeSerializer;
    }

    /**
     * Cast a scalar value using the schema.
     *
     * @param mixed  $value
     * @param Schema $schema
     *
     * @return float|int|string|\DateTimeInterface
     * @throws UnsupportedException
     */
    public function hydrate($value)
    {
        if (is_numeric($value)) {
            return $this->castNumber($value);
        }
        if ($value instanceof \stdClass) {
            $value = clone $value;
            foreach ($value as $name => $propertyValue) {
                $value->$name = $this->hydrate($propertyValue);
            }

            return $value;
        }
        if (is_array($value)) {
            return array_map(function ($itemValue)  {
                return $this->hydrate($itemValue);
            }, $value);
        }
        try {
            $value = $this->dateTimeSerializer->deserialize($value, $this->schema);
        } catch (\Throwable $e) {
            return $value;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return float|int
     */
    private function castNumber($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return !ctype_digit($value) ? (float)$value : (int)$value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function dehydrate($value)
    {
        // TODO: Implement dehydrate() method.
    }
}
