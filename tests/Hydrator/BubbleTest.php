<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class BubbleTest extends TestCase
{
    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @var MockObject
     */
    private $dateTimeSerializer;

    protected function setUp()
    {
        $this->hydrator = $this
            ->getMockBuilder(Hydrator::class)
            ->setMethods(['supports', 'hydrate'])
            ->getMock();
    }

    /**
     * @test
     */
    public function willFailWhenNoParent()
    {
        $this->expectException(UnsupportedException::class);
        $this->hydrator->bubble([], new AnySchema());
    }

    /**
     * @test
     */
    public function willUseParentIfSupports()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this->getMockForAbstractClass(Hydrator::class);
        $this->hydrator->setParent($parent);

        list($value, $schema) = [(object)[], new AnySchema()];

        $mockParent
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(true);

        $mockParent
            ->expects($this->once())
            ->method('hydrate')
            ->with($value, $schema)
            ->willReturn($value);

        $this->assertSame($value, $this->hydrator->bubble($value, $schema));
    }

    /**
     * @test
     */
    public function willBubbleWhenNotSupported()
    {
        /** @var Hydrator $parent */
        $mockParent = $parent = $this
            ->getMockBuilder(Hydrator::class)
            ->setMethods(['supports', 'hydrate'])
            ->getMock();
        $this->hydrator->setParent($parent);

        /** @var Hydrator $parentParent */
        $mockParentParent = $parentParent = $this
            ->getMockBuilder(Hydrator::class)
            ->setMethods(['supports', 'hydrate'])
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
            ->method('hydrate');

        $mockParentParent
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(true);

        $mockParentParent
            ->expects($this->once())
            ->method('hydrate')
            ->with($value, $schema)
            ->willReturn($value);

        $this->assertSame($value, $this->hydrator->bubble($value, $schema));
    }
}
