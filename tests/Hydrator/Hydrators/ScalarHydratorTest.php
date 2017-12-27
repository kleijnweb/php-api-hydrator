<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ScalarHydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScalarHydratorTest extends TestCase
{
    /**
     * @var ScalarHydrator
     */
    private $hydrator;

    /**
     * @var MockObject
     */
    private $dateTimeSerializer;

    protected function setUp()
    {
        /** @var DateTimeSerializer $serializer */
        $serializer = $this->dateTimeSerializer = $this->getMockBuilder(DateTimeSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new ScalarHydrator($serializer, true);
    }


    /**
     * @test
     */
    public function willPassThroughStringsWhenUsingAnySchema()
    {
        $this->assertSame(
            'foo',
            $this->hydrator->hydrate('foo', new ScalarSchema((object)['type' => Schema::TYPE_STRING]))
        );
    }

    /**
     * @test
     */
    public function willCastFloatStringToFloat()
    {
        $this->assertSame(
            1.0,
            $this->hydrator->hydrate('1.0', new ScalarSchema((object)['type' => Schema::TYPE_NUMBER]))
        );
    }

    /**
     * @test
     */
    public function willCastIntStringToInt()
    {
        $this->assertSame(
            1,
            $this->hydrator->hydrate('1.0', new ScalarSchema((object)['type' => Schema::TYPE_INT]))
        );
    }

    /**
     * @test
     */
    public function willDeserializeDateTime()
    {
        $schema     = new ScalarSchema((object)['type' => Schema::TYPE_STRING, 'format' => Schema::FORMAT_DATE_TIME]);
        $inputValue = 'anything';

        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($inputValue, $schema)
            ->willReturn($dateTime = new \DateTime());

        $this->assertSame(
            $dateTime,
            $this->hydrator->hydrate($inputValue, $schema)
        );
    }

    /**
     * @test
     */
    public function willFailWhenOnInt64SchemaWhenNotSupported()
    {
        $schema = new ScalarSchema((object)[
            'type'   => Schema::TYPE_INT,
            'format' => Schema::FORMAT_INT64,
        ]);

        $this->expectException(UnsupportedException::class);

        $this->hydrator->hydrate('anything', $schema);
    }

    /**
     * @test
     */
    public function willFailOnIntWhenTooLargeStringValue()
    {
        $schema = new ScalarSchema((object)['type' => Schema::TYPE_INT]);

        $this->expectException(UnsupportedException::class);

        $this->hydrator->hydrate((string)PHP_INT_MAX . 1, $schema);
    }

    /**
     * @test
     */
    public function willFailOnIntWhenTooLargeFloatValue()
    {
        $schema = new ScalarSchema((object)['type' => Schema::TYPE_INT]);

        $this->expectException(UnsupportedException::class);

        $this->hydrator->hydrate(((float)PHP_INT_MAX) * 2, $schema);
    }
}
