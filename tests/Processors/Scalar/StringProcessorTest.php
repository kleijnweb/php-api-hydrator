<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Processors\Scalar;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Processors\Scalar\StringProcessor;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class StringProcessorTest extends BasicScalarTest
{
    protected function setUp()
    {
        $this->processor = new StringProcessor($this->createSchema(Schema::TYPE_STRING, 'a'));
    }

    /**
     * @return array
     */
    public static function valueProvider()
    {
        return [
            ['1.0', '1.0'],
            ['2', '2'],
            [1.0, '1'],
            [2, '2'],
            [true, '1'],
            [false, ''],
        ];
    }
}
