<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\DateTimeNotParsableException;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DateTimeSerializer
{
    const DEFAULT_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * @var string
     */
    private $format;

    /**
     * DateTimeSerializer constructor.
     *
     * @param string $format
     */
    public function __construct(string $format = null)
    {
        $this->format = $format;
    }

    /**
     * @param \DateTimeInterface $value
     * @param Schema             $schema
     *
     * @return string
     */
    public function serialize(\DateTimeInterface $value, Schema $schema): string
    {
        if ($schema instanceof ScalarSchema && $schema->hasFormat(Schema::FORMAT_DATE)) {
            return $value->format('Y-m-d');
        }

        return $value->format($this->format ?: self::DEFAULT_FORMAT);
    }

    /**
     * @param mixed  $value
     * @param Schema $schema
     *
     * @return \DateTime
     *
     */
    public function deserialize($value, Schema $schema): \DateTime
    {
        if ($this->format) {
            if (false === $result = \DateTime::createFromFormat($this->format, $value)) {
                throw new DateTimeNotParsableException(
                    sprintf("Date '%s' not parsable as '%s'", $value, $this->format)
                );
            }

            return $result;
        }

        if ($schema instanceof ScalarSchema) {
            if ($schema->hasFormat(Schema::FORMAT_DATE)) {
                return new \DateTime("{$value} 00:00:00");
            }
        }

        return new \DateTime($value);
    }
}
