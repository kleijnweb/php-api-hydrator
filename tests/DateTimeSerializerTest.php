<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DateTimeSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function willSerializeDates()
    {
        $date = '2016-01-01';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE]);
        $actual = $serializer->serialize(new \DateTime($date), $schema);
        $this->assertSame($date, $actual);
    }
    /**
     * @test
     */
    public function willDeserializeDatesToMidnight()
    {
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE]);
        $actual = $serializer->deserialize('2016-01-01', $schema);
        $midnightFirstOfJanuary = new \DateTime('2016-01-01 00:00:00');
        $this->assertSame('000000000000', $midnightFirstOfJanuary->diff($actual)->format('%Y%M%D%H%I%S'));
    }

    /**
     * @test
     */
    public function willSerializeDateTime()
    {
        $dateTime = '2016-01-01T23:59:59+01:00';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE_TIME]);
        $actual = $serializer->serialize(new \DateTime($dateTime), $schema);
        $this->assertSame($dateTime, $actual);
    }

    /**
     * @test
     */
    public function willDeserializeDateTime()
    {
        $dateTime = '2016-01-01T23:59:59+01:00';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE_TIME]);
        $actual = $serializer->deserialize($dateTime, $schema);
        $midnightFirstOfJanuary = new \DateTime($dateTime);
        $this->assertSame('000000000000', $midnightFirstOfJanuary->diff($actual)->format('%Y%M%D%H%I%S'));
    }
}
