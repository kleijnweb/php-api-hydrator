<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\CompositeHydrator;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use PHPUnit\Framework\TestCase;

class CompositeHydratorTest extends TestCase
{
    /**
     * @test
     */
    public function addingChildSetsParent()
    {
        $composite = new CompositeHydrator();

        /** @var Hydrator $child */
        $child = $mockChild = $this->getMockBuilder(Hydrator::class)->getMock();

        $mockChild
            ->expects($this->once())
            ->method('setParent')
            ->with($composite);

        $composite->addChildStrategy($child);
    }

    /**
     * @test
     */
    public function supportMatchesOnFirstChild()
    {
        /** @var Hydrator $child1 */
        $child1 = $mockChild1 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child2 */
        $child2 = $mockChild2 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child3 */
        $child3 = $mockChild3 = $this->getMockBuilder(Hydrator::class)->getMock();

        $mockChild1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $mockChild2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $mockChild3
            ->expects($this->never())
            ->method('supports');

        $composite = new CompositeHydrator([$child1, $child2, $child3]);
        $composite->supports(true, new AnySchema());
    }

    /**
     * @test
     */
    public function hydrateWillFailWhenNoChildrenSupportArguments()
    {
        /** @var Hydrator $child1 */
        $child1 = $mockChild1 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child2 */
        $child2 = $mockChild2 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child3 */
        $child3 = $mockChild3 = $this->getMockBuilder(Hydrator::class)->getMock();

        $mockChild1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $mockChild2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $mockChild3
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $composite = new CompositeHydrator([$child1, $child2, $child3]);

        $this->expectException(UnsupportedException::class);
        $composite->hydrate(true, new AnySchema());
    }

    /**
     * @test
     */
    public function willHydrateOnFirstChildThatSupportsValueAndSchema()
    {
        /** @var Hydrator $child1 */
        $child1 = $mockChild1 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child2 */
        $child2 = $mockChild2 = $this->getMockBuilder(Hydrator::class)->getMock();
        /** @var Hydrator $child3 */
        $child3 = $mockChild3 = $this->getMockBuilder(Hydrator::class)->getMock();

        $value  = (object)[];
        $schema = new ObjectSchema((object)[], (object)[]);

        $mockChild1
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(false);

        $mockChild2
            ->expects($this->once())
            ->method('supports')
            ->with($value, $schema)
            ->willReturn(true);

        $mockChild2
            ->expects($this->once())
            ->method('hydrate')
            ->with($value, $schema);

        $mockChild3
            ->expects($this->never())
            ->method('supports');

        $mockChild3
            ->expects($this->never())
            ->method('hydrate');

        $composite = new CompositeHydrator([$child1, $child2, $child3]);
        $composite->hydrate($value, $schema);
    }
}
