<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Object;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Processors\AnyProcessor;
use KleijnWeb\PhpApi\Hydrator\Processors\Object\LooseSimpleObjectProcessor;

class LooseSimpleObjectProcessorTest extends ObjectProcessorTest
{
    /**
     * @test
     */
    public function willAssembleOutputObject()
    {
        $processor = $this->createProcessor([$this, 'factory'], 'a', 'b', 'c');

        $this->mockPropertyProcesser
            ->expects($this->exactly(3))
            ->method('dehydrate')
            ->withConsecutive([3], [2], [1])
            ->willReturnOnConsecutiveCalls('three', 'two', 'one');

        $actual = $processor->dehydrate((object)['a' => 3, 'b' => 2, 'c' => 1]);

        $this->assertEquals((object)['a' => 'three', 'b' => 'two', 'c' => 'one'], $actual);
    }

    /**
     * @test
     */
    public function willNotOmitNullValuesWhenDehydrating()
    {
        $processor = $this->createProcessor([$this, 'factory'], 'aInt', 'nullProperty');

        $object = (object)['aInt' => 1, 'nullProperty' => null];

        $this->mockPropertyProcesser
            ->expects($this->any())
            ->method('dehydrate')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $data = $processor->dehydrate($object);

        $this->assertSame(1, $data->aInt);
        $this->assertObjectHasAttribute('nullProperty', $data);
        $this->assertNull($data->nullProperty);
    }

    protected function factory(ObjectSchema $schema)
    {
        return new LooseSimpleObjectProcessor($schema, new AnyProcessor(new AnySchema(), new DateTimeSerializer()));
    }
}
