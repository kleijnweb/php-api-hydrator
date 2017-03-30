<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DateTimeSerializer
{
    /**
     * @var string
     */
    private $format;

    /**
     * DateTimeSerializer constructor.
     *
     * @param string $format
     */
    public function __construct(string $format = \DateTime::RFC3339)
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
        if (!$schema instanceof ScalarSchema) {
            return $value->format($this->format);
        }
        if ($schema->hasFormat(Schema::FORMAT_DATE)) {
            return $value->format('Y-m-d');
        }

        return $value->format($this->format);
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
        if (!$schema instanceof ScalarSchema) {
            return new \DateTime($value);
        }
        if ($schema->hasFormat(Schema::FORMAT_DATE)) {
            return new \DateTime("{$value} 00:00:00");
        }

        return \DateTime::createFromFormat($this->format, $value);
    }
}
