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
     * @param ScalarSchema       $schema
     *
     * @return string
     */
    public function serialize(\DateTimeInterface $value, ScalarSchema $schema): string
    {
        if ($schema->hasFormat(Schema::FORMAT_DATE)) {
            return $value->format('Y-m-d');
        }

        return $value->format($this->format);
    }

    /**
     * @param mixed        $value
     * @param ScalarSchema $schema
     *
     * @return \DateTime
     *
     */
    public function deserialize($value, ScalarSchema $schema): \DateTime
    {
        if ($schema->hasFormat(Schema::FORMAT_DATE)) {
            return new \DateTime("{$value} 00:00:00");
        }

        return \DateTime::createFromFormat(\DateTime::RFC3339, $value);
    }
}
