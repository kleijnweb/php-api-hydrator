<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ScalarHydrator;
use PHPUnit\Framework\TestCase;

class AnySchemaScalarHydratorTest extends TestCase
{
    /**
     * @var ScalarHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new ScalarHydrator(new DateTimeSerializer());
    }

    /**
     * @test
     */
    public function willPassThroughStringsWhenUsingAnySchema()
    {
        $this->assertSame('foo', $this->hydrator->hydrate('foo', new AnySchema()));
    }

    /**
     * @test
     */
    public function willCastFloatStringToFloat()
    {
        $actual = $this->hydrator->hydrate('1.0', new AnySchema());
        $this->assertInternalType('float', $actual);
        $this->assertSame($actual, 1.0);
    }

    /**
     * @test
     */
    public function willCastIntStringToInt()
    {
        $actual = $this->hydrator->hydrate('1', new AnySchema());
        $this->assertInternalType('integer', $actual);
        $this->assertSame($actual, 1);
    }

    /**
     * @test
     */
    public function willDeserializeDateTime()
    {
        $actual = $this->hydrator->hydrate('2017-12-01', new AnySchema());
        $this->assertInstanceOf(\DateTime::class, $actual);
        $this->assertSame('2017-12-01', $actual->format('Y-m-d'));
    }
}
