<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\DateTimeDehydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateTimeDehydratorTest extends TestCase
{
    /**
     * @var DateTimeDehydrator
     */
    private $dehydrator;

    /**
     * @var MockObject
     */
    private $serializer;

    protected function setUp()
    {
        /** @var DateTimeSerializer $serializer */
        $serializer = $this->serializer = $this->getMockBuilder(DateTimeSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dehydrator = new DateTimeDehydrator($serializer);
    }

    /**
     * @test
     */
    public function supportsInstancesOfDateTimeInterface()
    {
        $this->assertTrue(
            $this->dehydrator->supports(
                $this->getMockForAbstractClass(\DateTime::class),
                new AnySchema()
            )
        );
        $this->assertTrue(
            $this->dehydrator->supports(
                $this->getMockForAbstractClass(\DateTimeImmutable::class),
                new AnySchema()
            )
        );
        $this->assertFalse(
            $this->dehydrator->supports(
                (object)[],
                new AnySchema()
            )
        );
    }

    /**
     * @test
     */
    public function willDelegateToSerialize()
    {
        $value  = $this->getMockForAbstractClass(\DateTime::class);
        $schema = new AnySchema();
        $this->serializer->expects($this->once())->method('serialize')->with($value, $schema);

        $this->dehydrator->dehydrate($value, $schema);
    }
}

