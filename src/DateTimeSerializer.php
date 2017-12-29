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
    const FORMAT_RFC3339_USEC = 'Y-m-d\TH:i:s.uP';

    /**
     * @var string
     */
    private $outputFormat;

    /**
     * @var string
     */
    private $inputDateTimeFormats = [
        self::FORMAT_RFC3339_USEC,
        \DateTime::RFC3339_EXTENDED,
        \DateTime::RFC3339,
        \DateTime::ATOM,
    ];

    /**
     * DateTimeSerializer constructor.
     *
     * @param string|string[] ...$formats
     */
    public function __construct(string ...$formats)
    {
        if (isset($formats[0])) {
            $this->outputFormat = $formats[0];
        }

        $this->inputDateTimeFormats = $formats + $this->inputDateTimeFormats;
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

        return $value->format($this->outputFormat ?: self::FORMAT_RFC3339_USEC);
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
        if ($schema instanceof ScalarSchema && $schema->hasFormat(Schema::FORMAT_DATE)) {
            if (false === $result = \DateTime::createFromFormat('Y-m-d H:i:s', "$value 00:00:00")) {
                throw new DateTimeNotParsableException(
                    sprintf("'%s' not parsable in YYYY-MM-DD format", $value)
                );
            }
            return $result;
        }

        foreach ($this->inputDateTimeFormats as $format) {
            if (false !== $result = \DateTime::createFromFormat($format, $value)) {
                return $result;
            }
        }

        throw new DateTimeNotParsableException(
            sprintf("Datetime '%s' not parsable as one of '%s'", $value, implode(', ', $this->inputDateTimeFormats))
        );
    }
}
