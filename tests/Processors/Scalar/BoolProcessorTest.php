<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Scalar;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\BoolProcessor;
use PHPUnit\Framework\TestCase;

class BoolProcessorTest extends TestCase
{
    /**
     * @var BoolProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->processor = new BoolProcessor(new ScalarSchema((object)['type' => Schema::TYPE_BOOL]));
    }

    /**
     * @test
     * @dataProvider  valueProvider
     *
     * @param int|float $value
     * @param int|float $hydrated
     */
    public function willHydrateEverythingAsString($value, $hydrated)
    {
        $this->assertSame($hydrated, $this->processor->hydrate($value));
    }

    /**
     * @test
     * @dataProvider  valueProvider
     *
     * @param mixed $value
     */
    public function dehydrateWillAlwaysReturnValueAsIs($value)
    {
        $this->assertSame($value, $this->processor->dehydrate($value));
    }

    /**
     * @return array
     */
    public static function valueProvider()
    {
        return [
            ['1.0', true],
            ['2', true],
            [1.0, true],
            [2, true],
            [true, true],
            [false, false],
            ['', false],
            ['0', false],
            ['', false],
        ];
    }
}
