<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\CompositeDehydrator;
use PHPUnit\Framework\TestCase;

class CompositeDehydratorTest extends TestCase
{
    /**
     * @test
     */
    public function addingChildSetsParent()
    {
        $composite = new CompositeDehydrator();

        /** @var Dehydrator $child */
        $child = $mockChild = $this->getMockBuilder(Dehydrator::class)->getMock();

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
        /** @var Dehydrator $child1 */
        $child1 = $mockChild1 = $this->getMockBuilder(Dehydrator::class)->getMock();
        /** @var Dehydrator $child2 */
        $child2 = $mockChild2 = $this->getMockBuilder(Dehydrator::class)->getMock();
        /** @var Dehydrator $child3 */
        $child3 = $mockChild3 = $this->getMockBuilder(Dehydrator::class)->getMock();

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

        $composite = new CompositeDehydrator([$child1, $child2, $child3]);
        $composite->supports(true, new AnySchema());
    }

    /**
     * @test
     */
    public function willHydrateOnFirstChildThatSupportsValueAndSchema()
    {
        /** @var Dehydrator $child1 */
        $child1 = $mockChild1 = $this->getMockBuilder(Dehydrator::class)->getMock();
        /** @var Dehydrator $child2 */
        $child2 = $mockChild2 = $this->getMockBuilder(Dehydrator::class)->getMock();
        /** @var Dehydrator $child3 */
        $child3 = $mockChild3 = $this->getMockBuilder(Dehydrator::class)->getMock();

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
            ->method('dehydrate')
            ->with($value, $schema);

        $mockChild3
            ->expects($this->never())
            ->method('supports');

        $mockChild3
            ->expects($this->never())
            ->method('dehydrate');

        $composite = new CompositeDehydrator([$child1, $child2, $child3]);
        $composite->dehydrate($value, $schema);
    }
}
