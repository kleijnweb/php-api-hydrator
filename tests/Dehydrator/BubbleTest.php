<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class BubbleTest extends TestCase
{
    /**
     * @var Dehydrator
     */
    private $dehydrator;

    /**
     * @var MockObject
     */
    private $dateTimeSerializer;

    protected function setUp()
    {
        $this->dehydrator = $this
            ->getMockBuilder(Dehydrator::class)
            ->setMethods(['supports', 'dehydrate'])
            ->getMock();
    }

    /**
     * @test
     */
    public function willFailWhenNoParent()
    {
        $this->expectException(UnsupportedException::class);
        $this->dehydrator->bubble([], new AnySchema());
    }

    /**
     * @test
     */
    public function willUseParentIfSupports()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Dehydrator::class);
        $this->dehydrator->setParent($parent);

        list($value, $schema) = [(object)[], new AnySchema()];

        $mockParent
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(true);

        $mockParent
            ->expects($this->once())
            ->method('dehydrate')
            ->with($value, $schema)
            ->willReturn($value);

        $this->assertSame($value, $this->dehydrator->bubble($value, $schema));
    }

    /**
     * @test
     */
    public function willBubbleWhenNotSupported()
    {
        /** @var Dehydrator $parent */
        $mockParent = $parent = $this
            ->getMockBuilder(Dehydrator::class)
            ->setMethods(['supports', 'dehydrate'])
            ->getMock();
        $this->dehydrator->setParent($parent);

        /** @var Dehydrator $parentParent */
        $mockParentParent = $parentParent = $this
            ->getMockBuilder(Dehydrator::class)
            ->setMethods(['supports', 'dehydrate'])
            ->getMock();

        $parent->setParent($parentParent);

        list($value, $schema) = [(object)[], new AnySchema()];

        $mockParent
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(false);

        $mockParent
            ->expects($this->never())
            ->method('dehydrate');

        $mockParentParent
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(true);

        $mockParentParent
            ->expects($this->once())
            ->method('dehydrate')
            ->with($value, $schema)
            ->willReturn($value);

        $this->assertSame($value, $this->dehydrator->bubble($value, $schema));
    }
}
