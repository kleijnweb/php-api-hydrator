<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\DateTimeNotParsableException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ScalarHydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AnySchemaScalarHydratorTest extends TestCase
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

        $this->hydrator = new ScalarHydrator($serializer);
    }

    /**
     * @test
     */
    public function willPassThroughStringsWhenUsingAnySchema()
    {
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new DateTimeNotParsableException());

        $this->assertSame('foo', $this->hydrator->hydrate('foo', new AnySchema()));
    }

    /**
     * @test
     */
    public function willCastFloatStringToFloat()
    {
        $actual = $this->hydrator->hydrate('1.0', new AnySchema());
        $this->assertInternalType('float', $actual);
        $this->assertSame($actual, 1.0);
    }

    /**
     * @test
     */
    public function willCastIntStringToInt()
    {
        $actual = $this->hydrator->hydrate('1', new AnySchema());
        $this->assertInternalType('integer', $actual);
        $this->assertSame($actual, 1);
    }


    /**
     * @test
     */
    public function willDeserializeDateTime()
    {
        $this->dateTimeSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($dateTime = new \DateTime());

        $this->assertSame($dateTime, $this->hydrator->hydrate('2017-12-01', new AnySchema()));
    }
}
