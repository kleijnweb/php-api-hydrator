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
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NullProcessor;
use PHPUnit\Framework\TestCase;

class NullProcessorTest extends TestCase
{
    /**
     * @var NullProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->processor = new NullProcessor(new ScalarSchema((object)['type' => Schema::TYPE_NULL]));
    }

    /**
     * @test
     * @dataProvider  valueProvider
     */
    public function hydrateWillAlwaysReturnNull($value)
    {
        $this->assertNull($this->processor->hydrate($value));
    }

    /**
     * @test
     * @dataProvider  valueProvider
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
            [''],
            [0],
            [null],
            [[]],
            ['foo'],
            [(object)[]],
        ];

    }
}

