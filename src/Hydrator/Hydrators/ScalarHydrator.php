<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;

class ScalarHydrator extends Hydrator
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
    public function __construct(DateTimeSerializer $dateTimeSerializer, $force32Bit = false)
    {
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
    public function hydrate($value, Schema $schema)
    {
        if ($schema instanceof ScalarSchema) {

            if ($schema->isType(Schema::TYPE_INT)) {
                if ($this->is32Bit && $schema->hasFormat(Schema::FORMAT_INT64)) {
                    throw new UnsupportedException("Operating system does not support 64 bit integers");
                }

                return (int)$value;
            } elseif ($schema->isDateTime()) {
                return $this->dateTimeSerializer->deserialize($value, $schema);
            } elseif ($schema->isPrimitive(Schema::TYPE_NUMBER)) {
                return $this->castNumber($value);
            }

            settype($value, $schema->getType());
        } elseif ($schema instanceof AnySchema) {
            if (is_numeric($value)) {
                return $this->castNumber($value);
            }
            try {
                $value = $this->dateTimeSerializer->deserialize($value, $schema);
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * @param        $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return is_scalar($data);
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
}
