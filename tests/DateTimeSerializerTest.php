<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\DateTimeNotParsableException;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DateTimeSerializerTest extends TestCase
{
    public function testWillSerializeDates()
    {
        $date       = '2016-01-01';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE]);
        $actual     = $serializer->serialize(new \DateTime($date), $schema);
        self::assertSame($date, $actual);
    }

    public function testWillDeserializeDatesToMidnight()
    {
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)[
            'type'   => Schema::TYPE_STRING,
            'format' => Schema::FORMAT_DATE
        ]);

        $actual                 = $serializer->deserialize('2016-01-01', $schema);
        $midnightFirstOfJanuary = new \DateTime('2016-01-01 00:00:00');
        self::assertSame('000000000000', $midnightFirstOfJanuary->diff($actual)->format('%Y%M%D%H%I%S'));
    }

    public function testWillSerializeDateTime()
    {
        $dateTime   = '2016-01-01T23:59:59.000000+01:00';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE_TIME]);

        self::assertSame($dateTime, $serializer->serialize(new \DateTime($dateTime), $schema));
    }

    public function testWillDeserializeDateTime()
    {
        $dateTime   = '2016-01-01T23:59:59+01:00';
        $serializer = new DateTimeSerializer();
        $schema     = new ScalarSchema((object)[
            'type'   => Schema::TYPE_STRING,
            'format' => Schema::FORMAT_DATE_TIME
        ]);

        $actual                 = $serializer->deserialize($dateTime, $schema);
        $midnightFirstOfJanuary = new \DateTime($dateTime);

        self::assertSame('000000000000', $midnightFirstOfJanuary->diff($actual)->format('%Y%M%D%H%I%S'));
    }

    public function testWillDeserializeValueUsingAnySchemaByUsingDateTimeConstructor()
    {
        $dateTime   = 'midnight';
        $serializer = new DateTimeSerializer();
        $schema     = new AnySchema();
        $actual     = $serializer->deserialize($dateTime, $schema);

        self::assertEquals(new \DateTime($dateTime), $actual);
    }

    public function testWillSerializeValueUsingAnySchemaUsingDateTimeFormat()
    {
        $dateTime   = new \DateTime('midnight');
        $serializer = new DateTimeSerializer(\DateTime::RSS);
        $schema     = new AnySchema();
        $actual     = $serializer->serialize($dateTime, $schema);

        self::assertEquals($dateTime->format(\DateTime::RSS), $actual);
    }

    public function testWillThrowExceptionWhenDateNotParsableAccordingToFormat()
    {
        $serializer = new DateTimeSerializer(\DateTime::RSS);
        $schema     = new ScalarSchema((object)['format' => Schema::FORMAT_DATE]);

        self::expectException(DateTimeNotParsableException::class);

        $serializer->deserialize('2016-01-01T23:59:59+01:00', $schema);
    }

    public function testWillDeserializeValueUsingScalarSchemaUsingCustomDateTimeFormat()
    {
        $preciseDateTimeFormat = 'm-d-Y\TH:i:s.uP';
        $preciseDateTime       = '01-01-2010T23:45:59.000002+01:00';

        $schemaDefinition         = new \stdClass();
        $schemaDefinition->format = Schema::FORMAT_DATE_TIME;

        $schema = new ScalarSchema($schemaDefinition);

        $serializer = new DateTimeSerializer($preciseDateTimeFormat);
        $actualDate = $serializer->deserialize($preciseDateTime, $schema);

        self::assertEquals(\DateTime::createFromFormat($preciseDateTimeFormat, $preciseDateTime), $actualDate);
    }
}
