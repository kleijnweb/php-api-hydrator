<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Object;

use KleijnWeb\PhpApi\Hydrator\Processors\Object\StrictSimpleObjectProcessor;

class StrictSimpleObjectProcessorTest extends ObjectProcessorTest
{
    /**
     * @test
     */
    public function willAssembleOutputObject()
    {
        $processor = $this->createProcessor(StrictSimpleObjectProcessor::class, 'a', 'b', 'c');

        $this->mockPropertyProcesser
            ->expects($this->exactly(3))
            ->method('dehydrate')
            ->withConsecutive([3], [2], [1])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $actual = $processor->dehydrate((object)['a' => 3, 'b' => 2, 'c' => 1]);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }
}
