<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Scalar;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\NullProcessor;

class NullProcessorTest extends BasicScalarTest
{
    protected function setUp()
    {
        $this->processor = new NullProcessor($this->createSchema(Schema::TYPE_BOOL, null));
    }

    /**
     * @return array
     */
    public static function valueProvider()
    {
        return [
            ['', null],
            [0, null],
            [null, null],
            [[], null],
            ['foo', null],
            [(object)[], null],
        ];
    }
}

